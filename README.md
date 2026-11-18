<div align="center">

# 🎓 StudentHub

### *A Simple & Responsive Student Event Management Website*

<p>
An academic student event management project built using <strong>HTML5</strong>,
<strong>CSS3</strong>, <strong>JavaScript</strong>, <strong>PHP</strong>, and
<strong>MySQL</strong>.
</p>

<img src="https://readme-typing-svg.herokuapp.com?font=Poppins&weight=600&size=24&pause=1000&color=3B82F6&center=true&vCenter=true&width=600&lines=Student+Event+Management+Website;Built+with+HTML5+and+CSS3;Responsive+and+Beginner+Friendly" />

<br>

![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![Backend](https://img.shields.io/badge/Backend-PHP%20%2B%20MySQL-success?style=for-the-badge)
![Responsive](https://img.shields.io/badge/Responsive-Yes-brightgreen?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)

</div>

---

# 📖 About

**StudentHub** is a front-end web application designed for managing college events.

Students can browse events, register through the interface, manage their profiles, and view registered events. Administrators can access a separate dashboard to manage events.

This project was developed as a college academic project. Its public pages use HTML, CSS,
and JavaScript, while PHP and MySQL provide account registration, authentication, and
administrative features.

---

# ✨ Features

- 🏠 Home Page
- ℹ️ About Page
- 🎉 Events Page
- ❓ FAQ Section
- 📞 Contact Page
- 🔐 Login Interface
- 📝 Student Registration
- 👨‍🎓 Student Dashboard
- 👤 Student Profile
- 📅 My Events
- 👨‍💼 Admin Dashboard
- ⚙️ Manage Events
- 🌗 Persistent light/dark theme across user-facing pages
- ❎ Secure event registration cancellation with custom confirmation dialog

---

The main-site pages use one canonical PHP route per feature: `index.php`, `about.php`, `events.php`, `faq.php`, `contact.php`, `login.php`, and `register.php`. Duplicate HTML routes for these features have been removed.

# 🛠️ Tech Stack

| Technology | Usage |
|------------|-------|
| HTML5 | Website Structure |
| CSS3 | Styling & Layout |
| JavaScript | Client-side interactions and form validation |
| PHP | Registration, authentication, sessions, and server-side features |
| MySQL | Student accounts, student records, and application data |

---

# 📂 Project Structure

```text
StudentHub/
├── index.php  (session-aware home page)
├── about.php
├── contact.php
├── faq.php
├── login.php
├── register.php
├── dashboard.php
├── profile.php
├── events.php
├── myEvents.php
├── register_event.php
├── cancel_registration.php
├── adminDashboard.php
├── manageEvents.php
├── manageStudents.php
├── manageStudentsAjax.php
├── eventParticipants.php
├── includes/
│   ├── student_nav.php
│   ├── site_header.php
│   └── site_navigation.php
├── Styles/
│   ├── common.css
│   └── page-specific stylesheets
├── Scripts/
│   ├── practical4.js
│   ├── confirmationDialog.js
│   ├── formValidation.js
│   └── other page scripts
├── database/
├── storage/
├── api/
├── Practical6/
└── README.md
```

The main-site pages use one canonical PHP route per feature. Public pages share `includes/site_header.php` and `includes/site_navigation.php`; signed-in students use `includes/student_nav.php`. The only remaining HTML page is `Practical6/index.html`, a separate standalone practical rather than a duplicate main-site route. The theme switcher is shared across the main site and persists the light/dark choice.
Event cancellation and administrator delete actions use one shared accessible
confirmation dialog rather than browser-specific JavaScript confirmation popups.

# 🌐 Website Flow

```text
StudentHub
│
├── Home
├── About
├── Events
├── FAQ
├── Contact
├── Login
├── Register
│
├── Student Dashboard
│   ├── Profile
│   ├── My Events
│   └── Logout
│
└── Admin Dashboard
    ├── Manage Events
    └── Logout
```

---

# 📄 Website Pages

| Page | Description |
|------|-------------|
| Home | Landing page |
| About | Information about StudentHub |
| Events | College events |
| FAQ | Frequently asked questions |
| Contact | Contact information |
| Login | Student login page |
| Register | Student registration page |
| Dashboard | Student dashboard |
| Profile | Student profile |
| My Events | Registered events |
| Admin Dashboard | Administrator panel |
| Manage Events | Event management |

---

# 🚀 Getting Started

### Clone the Repository

```bash
git clone https://github.com/SAIYAM23/StudentHub.git
```

### Run with XAMPP

1. Start Apache and MySQL from XAMPP.
2. Place or link the `StudentHub` folder inside XAMPP's `htdocs` directory.
3. Create the `studenthub` database and import `database/studenthub.sql` for a fresh setup.
4. For an existing database, run `database/unified_registration_migration.sql` once. This creates the shared account/profile tables without deleting existing Practical 9 accounts.
5. Check the database credentials in `db.php` and `login.php` if your MySQL configuration differs from the default XAMPP setup.
6. Open `http://localhost/StudentHub/`.

The main registration page is `register.php`. It creates the account in `users`, the student record in `students`, and the additional form data in `student_profiles`. The same email/password pair is then used by `login.php`.

The Practical 9 academic registration exercise uses the single `practical9_register.php` route and is not linked from the normal StudentHub navigation.

---

# 📸 Screenshots

> Replace these with actual screenshots after uploading them.

| Home | Dashboard |
|------|-----------|
| ![](screenshots/home.png) | ![](screenshots/dashboard.png) |

| Events | Admin Dashboard |
|---------|-----------------|
| ![](screenshots/events.png) | ![](screenshots/admin.png) |

---

# 🎯 Future Enhancements

- ✅ JavaScript Validation
- ✅ PHP Authentication
- ✅ MySQL Database
- ✅ Event Registration System
- ✅ Admin Login
- ✅ Search Feature
- ✅ User Session Management
- ✅ Email Notifications

---

# 📚 What I Learned

During this project I practiced:

- HTML5
- CSS3
- Responsive Design
- Navigation
- Form Design
- Dashboard Layout
- Website Structure
- Project Organization

---

# 🤝 Contributing

Contributions are welcome.

If you'd like to improve this project:

1. Fork the repository
2. Create a feature branch

```bash
git checkout -b feature-name
```

3. Commit your changes

```bash
git commit -m "Add new feature"
```

4. Push the branch

```bash
git push origin feature-name
```

5. Open a Pull Request

---

# 👨‍💻 Author

**SAIYAMMM**

GitHub: **https://github.com/SAIYAM23/StudentHub.git**

---

<div align="center">

## ⭐ If you found this project helpful, don't forget to star the repository!

Made with ❤️ using **HTML5** and **CSS3**

</div>


### Dashboard and contact-page notes
- The admin dashboard treats the optional `audit_logs` table as optional and no longer marks the whole dashboard as failed when that table is absent.
- The login page uses a viewport-height layout so its footer sits at the bottom on short pages.
- The contact page no longer displays invented college details or a form that does not send messages. Configure official contact details and a delivery method before adding a live contact form.
- No database schema changes are required for these fixes.
