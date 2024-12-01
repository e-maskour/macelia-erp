# Macelia System

Macelia is a comprehensive, open-source enterprise resource planning (ERP) and customer relationship management (CRM) software solution designed to help small to medium-sized businesses streamline their operations. With Macelia, you can manage various aspects of your business, including sales, accounting, inventory, human resources, and more. The software is customizable, user-friendly, and scalable to fit the needs of your organization.

## Features

- **Customer Relationship Management (CRM):** Manage and track customer interactions, leads, and sales opportunities.
- **Sales Management:** Handle quotations, orders, invoices, and payments with ease.
- **Accounting & Finance:** Manage your financial operations, including ledgers, expenses, and taxes.
- **Inventory Management:** Track stock levels, manage warehouses, and automate procurement processes.
- **Human Resources:** Track employee data, manage attendance, and handle payroll.
- **Multi-Language & Multi-Currency Support:** Ideal for global businesses with support for multiple languages and currencies.

## Installation

### Requirements

Before installing Macelia, ensure you have the following:

- A web server with PHP 7.4 or later (Apache, Nginx, or similar)
- MySQL or MariaDB database
- A PHP-enabled server with the following extensions:
  - PDO
  - GD
  - cURL
  - MBstring
  - JSON
  - OpenSSL
- A mail server (for sending emails)

### Installation Steps

1. **Download Macelia:**
   - Clone the repository or download the latest release from the [Macelia GitHub repository](https://github.com/macelia/macelia).

2. **Setup the Web Server:**
   - Upload the Macelia files to your web server.
   - Configure the web server to serve the files (Apache: use `.htaccess` for URL rewriting or Nginx: configure proper server blocks).

3. **Create the Database:**
   - Log into your MySQL/MariaDB server.
   - Create a new database for Macelia:
     ```sql
     CREATE DATABASE macelia;
     ```

4. **Run the Installation Script:**
   - Navigate to the URL where you uploaded the Macelia files (e.g., `http://yourdomain.com/install`).
   - Follow the on-screen instructions to complete the installation, including configuring the database, admin user, and other settings.

5. **Complete Setup:**
   - Once installation is finished, you can log into the administration interface using the credentials you created.

## Configuration

After installation, you can configure various aspects of the Macelia system through the admin panel:

- **Company Settings:** Define your company details, logo, and preferences.
- **User Management:** Add and manage users, assign roles, and configure permissions.
- **Modules:** Enable or disable modules according to your business needs (e.g., HR, Sales, etc.).
- **Email Settings:** Configure email notifications and SMTP settings.

## Usage

Once Macelia is installed and configured, you can start using it to manage your business processes. Navigate through the various sections like Sales, CRM, Inventory, and Accounting to perform tasks such as:

- Creating quotes, orders, and invoices
- Managing customer and supplier data
- Running reports and tracking business performance

## Customization

Macelia is highly customizable, allowing you to adapt the system to your unique business requirements. You can:

- Install additional plugins or modules.
- Modify system settings from the admin panel.
- Integrate Macelia with other business tools via the API.

## Contributing

We welcome contributions from the community. If you'd like to contribute to the development of Macelia, follow these steps:

1. Fork the repository.
2. Create a new branch (`git checkout -b feature/your-feature`).
3. Make your changes and commit them (`git commit -m 'Add new feature'`).
4. Push to your forked repository (`git push origin feature/your-feature`).
5. Create a pull request.

Please ensure that your code adheres to the project's coding standards and includes tests where appropriate.
