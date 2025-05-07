# GeoTopBrands - Casino Brand Management System

A modern web application for managing casino brands with geolocation-based configuration. Built with Laravel (Backend) and Vanilla JS (Frontend).

## 🚀 Features

- CRUD operations for casino brands
- Responsive, mobile-friendly design
- Server-side pagination
- Geolocation-based bonus system
- Real-time form validation
- Modern UI with animations
- Loading states and notifications
- Image handling with fallback support

## 🛠 Tech Stack

### Backend
- PHP 8.1+
- Laravel 10.x
- MySQL 8.0+
- Docker for containerization

### Frontend
- Vanilla JavaScript (ES6+)
- Bootstrap 5.3
- Bootstrap Icons
- Custom CSS with animations

## 📦 Installation

### Prerequisites
- Ensure you have [Docker](https://www.docker.com/get-started) and [Docker Compose](https://docs.docker.com/compose/) installed on your machine.

### Steps to Launch the Project

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/geotopbrands.git
   cd geotopbrands
   ```

2. **Start Docker containers:**
   ```bash
   docker-compose up -d
   ```

3. **Install backend dependencies:**
   ```bash
   docker-compose exec php composer install
   ```

4. **Copy the environment file and generate the application key:**
   ```bash
   cp api/.env.example api/.env
   docker-compose exec php php artisan key:generate
   ```

5. **Run database migrations:**
   ```bash
   docker-compose exec php php artisan migrate
   ```

6. **Access the application:**
   - Open your web browser and navigate to `http://localhost:3000` to access the frontend.
   - The backend API can be accessed at `http://localhost:8080/api`.

## 🔧 Configuration

### Backend (Laravel)
- Database configuration can be found in `api/.env`.
- CORS settings are located in `api/config/cors.php`.
- Pagination settings can be adjusted in `api/config/custom.php`.

### Frontend
- API endpoint configuration is in `frontend/js/app.js`.
- Styling customization can be done in `frontend/css/style.css`.

## 📚 API Documentation

### Endpoints

#### GET /api/brands
Get a paginated list of brands.

**Query parameters:**
- `page`: Current page number (default: 1)
- `per_page`: Items per page (default: 5)

**Response format:**
```json
{
    "data": [
        {
            "id": 1,
            "brand_name": "Example Casino",
            "brand_image": "https://example.com/image.jpg",
            "country_code": "FR",
            "rating": 5
        }
    ],
    "current_page": 1,
    "per_page": 5,
    "total": 20,
    "last_page": 4
}
```

#### POST /api/brands
Create a new brand.

**Request body:**
```json
{
    "brand_name": "New Casino",
    "brand_image": "https://example.com/image.jpg",
    "country_code": "FR",
    "rating": 5
}
```

#### PUT /api/brands/{id}
Update an existing brand.

#### DELETE /api/brands/{id}
Delete a brand.

## 🌐 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 🔒 Security

- CSRF protection
- XSS prevention
- Input validation
- Secure image handling

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors
- Abel T. - Initial work and maintenance

## 🙏 Acknowledgments

- Bootstrap team for the amazing framework
- Laravel team for the robust backend framework
- All contributors who help improve this project