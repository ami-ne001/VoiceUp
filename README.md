# 🎙️ VoiceUp - Empowering Change Together

VoiceUp is a modern, dynamic petition platform designed to give everyone a voice. Whether it's a local community issue or a global movement, VoiceUp provides the tools to create, share, and sign petitions with ease.


## 🚀 Key Features

- **Dynamic Petition Dashboard**: Browse, search, and sort petitions by popularity, recentness, or deadline.
- **Real-time Trending**: Features a live-updating "Trending Petition" section powered by AJAX.
- **Petition Management**: Users can create, modify, and delete their own petitions.
- **Secure Signing**: Robust signature system to prevent duplicates and ensure integrity.
- **User Profiles**: Personalized profiles to track your created and signed petitions.
- **Image Uploads**: Support for custom petition banners to increase engagement.
- **Responsive Design**: Built with a sleek, premium UI using Tailwind CSS, fully optimized for all devices.

## 🛠️ Tech Stack

- **Backend**: PHP 8.x
- **Database**: MySQL
- **Frontend**: Tailwind CSS, Vanilla JavaScript, AJAX
- **Icons**: Font Awesome / Lucide-style SVG icons
- **Server**: Optimized for XAMPP/Apache environments

## 📥 Installation

Follow these steps to get VoiceUp running on your local machine:

1. **Clone the Repository**
   ```bash
   git clone https://github.com/ami-ne001/VoiceUp.git
   cd VoiceUp
   ```

2. **Database Setup**
   - Open your MySQL management tool (e.g., phpMyAdmin).
   - Create a new database named `petition_db`.
   - Import the `database/schema.sql` file into your newly created database.

3. **Configuration**
   - Navigate to `config/database.php`.
   - Update the database credentials to match your local setup:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', ''); // Your DB password
     define('DB_NAME', 'petition_db');
     ```

4. **Web Server Deployment**
   - Move the project folder to your web server's root directory (e.g., `C:/xampp/htdocs/`).
   - Open your browser and navigate to `http://localhost/VoiceUp`.

## 📂 Project Structure

```text
VoiceUp/
├── api/             # Backend API endpoints (JSON responses)
├── assets/          # CSS, JS, and image assets
├── config/          # Database and session configurations
├── database/        # SQL schema files
├── includes/        # Reusable UI components (header, navbar, footer)
├── uploads/         # User-uploaded petition images
├── index.php        # Main landing page
└── ... (other pages)
```

