# Hospital Appointment and Records System
A fullstack laravel project for COMP 016 - Web Development.

## Prerequisites
Docker Desktop for Windows
Composer
Git

## Installation and setup
Clone the repo
```bash 
git clone [https://github.com/Xuinnz/webdev-project.git](https://github.com/Xuinnz/webdev-project.git)
cd webdev-project
```

Create local env config
``` bash
cp .env.example .env
```

Configure env
``` bash
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=webdev_proj
DB_USERNAME=root
DB_PASSWORD=your_secure_password #put ur password here
```

Initialize Docker
``` bash 
docker compose up -d
```

Config docker
``` bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

Access the environment
``` bash
http://localhost:8000
```
To connect GUI client to the docker. create a connection and use these credentials
``` bash
Hostname: 127.0.0.1
Port: 43306
Username: root
Password: The DB_PASSWORD value you defined in your .env file.
```

## Docker Operations
To stop the container
``` bash
docker compose down
```

For other installations like npm, you can run just run it locally.
