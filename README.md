# 2FA-API

## Description
Use Symfony 5.4 to create a 2FA API with google authenticator

## Installation
```
composer install
```

## Database
```
bin/console make:migration
bin/console doctrine:migrations:migrate
```

## Usage
Use with Postman to test the API
### Register
```
POST /api/register
```
```
{
    "email": "email@example"
    "password": "password"
}
```
#### Response
```
"user": {
    "id": number,
    "email": "email@example",
    "password": "password",
},
"QrcodeUrl": "url"
```
### Login
```
POST /api/login
```
```
{
    "email": "email@example"
    "password": "password"
}
```
#### Response
```
{
    "user": {
        "id": number,
        "email": "email@example",
        "password": "password",
    },
    "token": {
        "token": "token"
    }
}
```

### Verify
```
POST /api/2FA
```
```
Authorization => Bearer Token

{
    "code": "code"
}
```

### User info
```
GET /api/user/info
```
```
Authorization => Bearer Token
```
#### Response
```
{
    "user": {
        "id": number,
        "email": "email@example",
        "password": "password",
    }
}
```
If 2FA not checked
```
{
    "message": "2FA verification needed"
}
```