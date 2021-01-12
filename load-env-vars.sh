#!/bin/bash

# Load environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '#' | awk '/=/ {print $1}')
else
    printf "No .env file found. Please run:\n\ncp .env.example .env\n\nbefore running this command.\n"
    exit 1
fi
