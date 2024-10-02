# Use the official PHP image as a base image
FROM php:8.1-apache

# Install necessary PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install system dependencies and Python, including python3-venv
RUN apt-get update && \
    apt-get install -y python3 python3-pip python3-dev python3-venv libgl1-mesa-glx libglib2.0-0 libatlas-base-dev libhdf5-dev && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Create a Python virtual environment
RUN python3 -m venv /var/www/html/venv

# Upgrade pip in the virtual environment
RUN /var/www/html/venv/bin/pip install --no-cache-dir --upgrade pip

# Copy the requirements.txt file into the container
COPY ./php/requirements.txt /tmp/requirements.txt

# Install required Python packages from requirements.txt
RUN /var/www/html/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt

# Copy the current directory contents into the container at /var/www/html
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Ensure Apache can use the Python environment in your PHP scripts
ENV VIRTUAL_ENV=/var/www/html/venv
ENV PATH="/var/www/html/venv/bin:$PATH"

# Expose port 80
EXPOSE 80

# Set the command to run the Apache server
CMD ["apache2-foreground"]
