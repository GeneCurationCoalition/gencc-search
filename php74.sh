#!/bin/bash

# Remove current PHP from PATH
export PATH=$(echo $PATH | tr ":" "\n" | grep -v "/opt/homebrew/opt/php" | tr "\n" ":" | sed 's/:$//')

# Add PHP 7.4 to PATH
export PATH="/opt/homebrew/opt/php@7.4/bin:/opt/homebrew/opt/php@7.4/sbin:$PATH"

# Set PHP 8.1 as the current version
export PHPRC="/opt/homebrew/etc/php/7.4/php.ini"

# # Start a new shell with this configuration
# exec $SHELL 
