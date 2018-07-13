# activeCollab API extension

Simple activeCollab API connecting directly to MySQL server database.

Necessary since the office API does not 

## Installation

Install with composer via packagist with the command:

`composer create-project phorax/activecollab-api`

## Setup

Create `.env` file with activeCollab MySQL server credentials as a copy of `.env.dist`.

## How-to use

Send GET request to the endpoint url with the corresponding payload.

## Authentication

Send `X-Angie-AuthApiToken` header with your [API key](https://developers.activecollab.com/api-documentation/v1/authentication.html). 

## Endpoints

### tasks/{id}

Retrieve project id.