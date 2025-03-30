#!/bin/sh

# Install dependencies if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
  echo "Installing dependencies..."
  npm install
fi

# Install dependencies in /public if needed
if [ ! -d "public/node_modules" ]; then
  echo "Installing dependencies in /public..."
  cd /public && npm install && cd ..
fi

# Start the application
if [ "$NODE_ENV" = "production" ]; then
  echo "Starting in production mode..."
  npm run start
else
  echo "Starting in development mode..."
  npm run dev
fi