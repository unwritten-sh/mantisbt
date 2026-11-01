#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# MantisBT harness - local instance for development and integration tests.
#
# Mirrors .github/workflows/mantisbt.yml on a developer machine:
#   database in Docker (compose.yaml), PHP built-in web server on the host,
#   web installer (admin/install.php), then build/ci_post_install.sh for
#   seed data, API token and tests/bootstrap.php.
#
# Usage: build/local_instance.sh <command> [args]
#   up                   Start the database container and wait until healthy
#   install [--reset]    Install MantisBT if config/config_inc.php is missing
#                        (--reset: drop the database and reinstall)
#   serve                Install if needed, then run the web server (foreground)
#   test [phpunit args]  Reinstall from scratch, start the web server and run
#                        vendor/bin/phpunit (all suites of phpunit.xml)
#   reset                Drop the database, delete config/config_inc.php and
#                        tests/bootstrap.php
#   down                 Stop containers and delete their volumes
#
# Settings come from the environment or from .env (see .env.example).
# Compatible with bash 3.2 (macOS default).
# -----------------------------------------------------------------------------
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [ -f .env ]; then
	set -a
	# shellcheck disable=SC1091
	. ./.env
	set +a
fi

MANTIS_HOST="${MANTIS_HOST:-127.0.0.1}"
PORT="${PORT:-8989}"
DB_TYPE="${DB_TYPE:-mysqli}"
DB_NAME="${DB_NAME:-bugtracker}"
DB_USER="${DB_USER:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"

MANTIS_CONFIG=config/config_inc.php
MANTIS_BOOTSTRAP=tests/bootstrap.php
SERVER_LOG="${TMPDIR:-/tmp}/mantisbt-php-server.log"
PHP_INI_ARGS=( -d "sendmail_path=$ROOT/tests/fakesendmail.sh" -d opcache.jit=disable )

case "$DB_TYPE" in
	mysqli)
		COMPOSE=( docker compose )
		SERVICE=mysql
		;;
	pgsql)
		COMPOSE=( docker compose --profile pgsql )
		SERVICE=pgsql
		;;
	*)
		echo "Unsupported DB_TYPE '$DB_TYPE' (use mysqli or pgsql)" >&2
		exit 1
		;;
esac

# Run one SQL statement on the MantisBT database (same contract as DB_CMD in CI)
db_cmd_string() {
	if [ "$DB_TYPE" = mysqli ]; then
		echo "${COMPOSE[*]} exec -T $SERVICE mysql -u$DB_USER -p$DB_PASSWORD $DB_NAME -e"
	else
		echo "${COMPOSE[*]} exec -T $SERVICE psql -q -U $DB_USER $DB_NAME -c"
	fi
}

cmd_up() {
	"${COMPOSE[@]}" up -d --wait "$SERVICE"
}

cmd_reset() {
	cmd_up
	echo "Dropping and recreating database '$DB_NAME'"
	if [ "$DB_TYPE" = mysqli ]; then
		"${COMPOSE[@]}" exec -T "$SERVICE" mysql -u"$DB_USER" -p"$DB_PASSWORD" \
			-e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" 2>/dev/null
	else
		"${COMPOSE[@]}" exec -T "$SERVICE" psql -q -U "$DB_USER" -d postgres \
			-c "DROP DATABASE IF EXISTS $DB_NAME WITH (FORCE);" -c "CREATE DATABASE $DB_NAME;"
	fi
	rm -f "$MANTIS_CONFIG" "$MANTIS_BOOTSTRAP"
}

SERVER_PID=""
start_server() {
	if curl -s -o /dev/null "http://$MANTIS_HOST:$PORT/"; then
		echo "Port $PORT on $MANTIS_HOST is already in use. Stop that server or set PORT." >&2
		exit 1
	fi
	php "${PHP_INI_ARGS[@]}" -S "$MANTIS_HOST:$PORT" -t "$ROOT" >"$SERVER_LOG" 2>&1 &
	SERVER_PID=$!
	trap stop_server EXIT
	for _ in $(seq 1 50); do
		if curl -s -o /dev/null "http://$MANTIS_HOST:$PORT/"; then
			echo "PHP web server on http://$MANTIS_HOST:$PORT/ (PID $SERVER_PID, log $SERVER_LOG)"
			return 0
		fi
		sleep 0.2
	done
	echo "PHP web server did not start, see $SERVER_LOG" >&2
	exit 1
}

stop_server() {
	if [ -n "$SERVER_PID" ]; then
		kill "$SERVER_PID" 2>/dev/null || true
		wait "$SERVER_PID" 2>/dev/null || true
		SERVER_PID=""
	fi
}

run_installer() {
	# Same parameters as build/ci_install_mantis.sh (which needs bash 4)
	echo "Running admin/install.php"
	curl -s --fail-with-body -o /dev/null \
		--data "install=2&db_type=$DB_TYPE&hostname=$MANTIS_HOST&database_name=$DB_NAME" \
		--data "db_username=$DB_USER&db_password=$DB_PASSWORD" \
		--data "admin_username=$DB_USER&admin_password=$DB_PASSWORD&timezone=UTC&path=" \
		"http://$MANTIS_HOST:$PORT/admin/install.php"
	if [ ! -f "$MANTIS_CONFIG" ]; then
		echo "Installer did not write $MANTIS_CONFIG, see $SERVER_LOG" >&2
		exit 1
	fi

	# ci_post_install.sh calls sudo to chmod the config file; not needed locally
	sudo() { "$@"; }
	export -f sudo
	HOSTNAME="$MANTIS_HOST" PORT="$PORT" DB_CMD="$(db_cmd_string)" \
		bash build/ci_post_install.sh 2> >(grep -v 'Using a password on the command line' >&2)
	unset -f sudo
}

cmd_install() {
	if [ "${1:-}" = "--reset" ] || [ ! -f "$MANTIS_CONFIG" ]; then
		cmd_reset
		start_server
		run_installer
		stop_server
		echo "MantisBT installed. Log in as $DB_USER / $DB_PASSWORD."
	else
		cmd_up
		echo "MantisBT already installed ($MANTIS_CONFIG exists). Use --reset to reinstall."
	fi
}

cmd_serve() {
	cmd_install
	echo "Serving http://$MANTIS_HOST:$PORT/ (Ctrl+C to stop)"
	exec php "${PHP_INI_ARGS[@]}" -S "$MANTIS_HOST:$PORT" -t "$ROOT"
}

cmd_test() {
	cmd_install --reset
	start_server
	php "${PHP_INI_ARGS[@]}" vendor/bin/phpunit "$@"
}

t_command="${1:-}"
[ $# -gt 0 ] && shift
case "$t_command" in
	up) cmd_up ;;
	install) cmd_install "$@" ;;
	serve) cmd_serve ;;
	test) cmd_test "$@" ;;
	reset) cmd_reset ;;
	down) "${COMPOSE[@]}" down -v ;;
	*)
		sed -n '2,24p' "$0"
		exit 1
		;;
esac
