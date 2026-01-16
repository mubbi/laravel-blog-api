#!/bin/bash
# Wrapper script for mysqldump that adds --skip-ssl flag
/usr/bin/mysqldump --skip-ssl "$@"
