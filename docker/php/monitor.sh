#!/bin/sh

exec /srv/app/bin/console monitor "$IP_CHECKING_DSN" "$DNS_SERVICE_DSN"
