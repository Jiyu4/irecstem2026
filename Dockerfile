FROM php:8.2-cli

WORKDIR /app

# Copy application files
COPY . /app

# Expose port 10000
EXPOSE 10000

# Start PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000"]
