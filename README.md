# DailomaBhariya

DailomaBhariya is a web-based platform designed to connect customers with local grocery stores through a simple and efficient ordering system. The project focuses on providing an easy shopping experience for customers while giving administrators the tools to manage products, orders, and users.

This project is currently under active development, and new features are being added continuously.

## Project Status

**Development Phase**

The application is not yet deployed. Features, structure, and documentation may change as development progresses.

## Features

### Customer

- User registration and login
- Browse products
- Browse products by category
- Product details page
- Shopping cart
- Place orders
- Track order status

### Administrator

- Secure admin login
- Dashboard
- Product management
- Order management
- Customer management
- Inventory management

### General

- Authentication system
- Responsive interface
- Database-driven application
- Modular PHP structure
- REST-style API endpoints

## Tech Stack

### Frontend

- HTML5
- CSS3
- JavaScript

### Backend

- PHP

### Database

- MySQL

## Project Structure

```
DailomaBhariya/
│
├── admin/
├── api/
├── assets/
│   ├── css/
│   └── js/
├── config/
├── customer/
├── includes/
├── sql/
├── index.php
├── login.php
├── register.php
├── category.php
├── item-details.php
├── track.php
└── README.md
```

## Installation

### Requirements

- PHP 8 or later
- MySQL
- Apache (XAMPP, WAMP, Laragon, or similar)

### Setup

1. Clone the repository.

```bash
git clone https://github.com/nishanpoudel108/DailomaBhariya.git
```

2. Move the project to your web server directory.

3. Create a new MySQL database.

4. Import the SQL file located in:

```
sql/database.sql
```

5. Configure the database connection in:

```
includes/database.php
```

6. Start Apache and MySQL.

7. Open the project in your browser.

```
http://localhost/DailomaBhariya
```

## Current Development

The following features are planned or currently being developed:

- Online payment integration
- Delivery partner module
- Store management
- Product search improvements
- Wishlist
- Reviews and ratings
- Notifications
- Order history enhancements
- Analytics dashboard
- Security improvements

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a new branch.

```bash
git checkout -b feature/feature-name
```

3. Commit your changes.

```bash
git commit -m "Add feature"
```

4. Push the branch.

```bash
git push origin feature/feature-name
```

5. Open a Pull Request.

## Roadmap

- [ ] Online Payments
- [ ] Delivery Partner Dashboard
- [ ] Store Management
- [ ] Notifications
- [ ] Wishlist
- [ ] Product Reviews
- [ ] Advanced Search
- [ ] Analytics
- [ ] REST API Improvements
- [ ] Mobile Optimization

## Live Demo

A live version is not available at this time.

The project is still under development and will be deployed after the core functionality is completed.

## License

This project is licensed under the MIT License.

## Author

Developed by **NISHAN POUDEL**.

Feedback, suggestions, and contributions are welcome.
