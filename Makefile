
start-server:
	@echo "🔥 Starting server with docker compose..."
	docker compose up -d

stop-server:
	@echo "Stopping server..."
	docker compose stop

run-tests:
	@echo "🔥 Running tests with coverage..."
	./vendor/bin/pest --coverage
