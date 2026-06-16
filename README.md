# Hospital Appointment and Records System
A fullstack laravel project for COMP 016 - Web Development.

## Installation
Open WSL terminal and execute the following commands to update the package manager and install required system dependencies.
``` bash
sudo apt update && sudo apt upgrade -y

sudo apt install php-cli php-common php-mbstring php-xml php-bcmath php-curl php-tokenizer php-zip unzip git -y
```

Install Composer locally inside WSL
```bash 
curl -sS https://getcomposer.org installer | php 

sudo mv composer.phar /usr/local/bin/composer
```

Verify installation by checking the version

``` bash
composer --version
```

## Clone
Clone the repo
```bash 
cd ~
git clone https://github.com/Xuinnz/webdev-project.git
cd https://github.com/Xuinnz/webdev-project.git
composer install
```


## Database Configuration
install mysql database server and php-mysql extension
``` bash
sudo apt install mysql-server -y
sudo service mysql start
sudo apt install php-mysql -y
```

Log in to mysql cli
``` bash
sudo mysql
```
Execute this command to create local schema
``` bash
CREATE DATABASE webdev_proj;
```
ERROR: Access denied for user 'root'@'localhost'
``` bash
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_secure_password'; #put your own password
FLUSH PRIVILEGES;
EXIT;
```

## Environment Configuration
update your .env
``` bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webdev_proj 
DB_USERNAME=root
DB_PASSWORD=password123 #your password
```

## Run
``` bash
php artisan config:clear #clear cache
php artisan migrate #generate database schema
php artisan serve #run server
```





