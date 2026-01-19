# Media Module

The `Media` module provides centralized management for file uploads and digital assets. it leverages
`spatie/laravel-medialibrary` to handle file associations with domain models.

## Purpose

- **Asset Management:** Standardizes how files are stored, retrieved, and processed.
- **Association:** Easily attaches images or documents to any Eloquent model.
- **Security:** Provides a controlled layer for accessing uploaded files.

## Key Features

- **Model Integration**: Pre-configured traits for domain models to interact with the media library.
- **Avatar & Logo Support**: Specialized handling for profile pictures and institution logos.
