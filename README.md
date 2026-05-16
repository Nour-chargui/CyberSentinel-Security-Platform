# 🛡️ CyberSentinel v2.0
> **Intelligent Behavioral Security & E-commerce Platform**

CyberSentinel is a high-end web application that merges a premium E-commerce experience with an advanced **UEBA (User and Entity Behavior Analytics)** engine. Built with a focus on cybersecurity, the platform dynamically calculates user trust levels based on real-time interactions.

![License](https://img.shields.io/badge/Security-Advanced-blue)
![PHP](https://img.shields.io/badge/Backend-PHP%208.1-777bb4)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479a1)
![Tailwind](https://img.shields.io/badge/Frontend-Tailwind%20CSS-38b2ac)

---

## 🚀 Key Features

### 👤 Client Universe (Premium E-commerce)
- **Smart Onboarding:** Real-time password strength meter and recommendation engine.
- **Dynamic Profile Management:** Self-service avatar selection, email, phone, and security key updates.
- **Trust Index Dashboard:** Visual representation of the user's security standing using Chart.js.
- **Secured Shop:** A catalog of 13+ tech products with a risk-aware checkout process.
- **Order History:** Full traceability of certified purchases.

### 🛡️ Admin Universe (Security War Room)
- **Live Network Monitoring:** Real-time AJAX polling graph showing system request frequency.
- **Forensic Audit:** Deep-dive investigation into user activity timelines and login failures.
- **Kill-Switch Control:** Immediate ability to block or delete accounts identified as hostile.
- **Risk Remediation:** Capability to reset trust scores after manual identity verification.

---

## 🔒 Security Architecture

### 🧠 Behavioral Risk Scoring (BRS)
The platform uses a weighted algorithm to calculate a threat level (0-100%):
- **Brute-Force Detection:** +20% risk per failed login attempt.
- **Temporal Anomalies:** Penalty for suspicious activities between 1 AM and 5 AM.
- **Session Integrity:** Automated logout if a browser's User-Agent changes (Anti-Hijacking).
- **Threshold-Based Protection:** Transactions are automatically blocked if the risk score exceeds 80%.

### 🛡️ Defensive Coding
- **Anti-SQL Injection:** Systemic use of **PDO** with prepared statements.
- **Anti-XSS:** Contextual escaping using a specialized helper function `h($s)`.
- **Cryptography:** Passwords secured using the **BCRYPT** hashing algorithm.
- **Secure Recovery:** Multi-channel OTP (One Time Password) via real email simulation with 15-minute expiration.

---

## 🛠️ Tech Stack
- **Backend:** Pure PHP 8.1 (Modular Architecture).
- **Database:** MySQL (InnoDB with `ON DELETE CASCADE` constraints).
- **Frontend:** Tailwind CSS, JavaScript (ES6), Chart.js, Animate.css.
- **Optimization:** Dynamic image rendering pipeline (Unsplash API) for sub-1s loading times.

---

## 💻 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/CyberSentinel.git
