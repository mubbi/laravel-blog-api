#!/bin/bash
# Wrapper script for mysql that adds --skip-ssl flag
/usr/bin/mysql --skip-ssl "$@"
