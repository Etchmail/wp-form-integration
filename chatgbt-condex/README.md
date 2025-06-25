# ChatGBT Condex

This directory contains a basic skeleton for a Laravel + React (TypeScript) application built with Laravel Breeze and HeroUI components.

The project is intended as the starting point for the **ChatGBT Condex** vCard generator and management system.

## Structure

- `app/Models` – Placeholder Eloquent models
- `database/migrations` – Example migration files for core entities
- `resources/js` – Placeholder React components

This codebase does not include the full Laravel or Node.js setup due to environment limitations. Instead, it outlines the expected files so the project can be completed with a standard Laravel installation.


## Getting Started

1. Install [Laravel](https://laravel.com/) and create a new project.
2. Copy the files in this directory into your Laravel project.
3. Install Laravel Breeze with React and TypeScript:
   ```bash
   composer require laravel/breeze --dev
   php artisan breeze:install react
   npm install && npm run dev
   ```
4. Configure your database connection and run migrations:
   ```bash
   php artisan migrate
   ```
5. Implement additional controllers, routes and React pages to complete the application features.

The skeleton includes migrations and models for Resellers, Businesses, Profiles, and Templates. React components should be placed in `resources/js` and styled using HeroUI.

