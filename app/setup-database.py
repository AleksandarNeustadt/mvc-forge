#!/usr/bin/env python3
"""
Interactive Database Setup Script
Allows pasting passwords without showing them
"""

import getpass
import subprocess
import sys
import re

def print_colored(text, color='green'):
    """Print colored text"""
    colors = {
        'green': '\033[0;32m',
        'yellow': '\033[1;33m',
        'red': '\033[0;31m',
        'blue': '\033[0;34m',
        'nc': '\033[0m'  # No color
    }
    print(f"{colors.get(color, '')}{text}{colors['nc']}")

def get_password_input(prompt="Password", confirm=False):
    """Get password input (hidden, supports paste)"""
    while True:
        password = getpass.getpass(f"{prompt}: ")
        
        if not password:
            print_colored("❌ Password cannot be empty!", 'red')
            continue
        
        if confirm:
            confirm_password = getpass.getpass("Confirm password: ")
            if password != confirm_password:
                print_colored("❌ Passwords don't match! Try again.", 'red')
                continue
        
        return password

def execute_mysql_commands(commands, root_password=None):
    """Execute MySQL commands"""
    cmd = ['mysql', '-u', 'root']
    
    if root_password:
        # Use password via command line (less secure but works)
        cmd.extend(['-p' + root_password])
    else:
        cmd.append('-p')
    
    try:
        process = subprocess.Popen(
            cmd,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True
        )
        stdout, stderr = process.communicate(input=commands, timeout=30)
        
        if process.returncode != 0:
            if "Access denied" in stderr:
                return False, "Access denied. Check your MySQL root password."
            return False, stderr
        
        return True, stdout
    except subprocess.TimeoutExpired:
        return False, "Command timed out"
    except FileNotFoundError:
        return False, "MySQL client not found. Install with: sudo apt install mysql-client"
    except Exception as e:
        return False, str(e)

def main():
    print_colored("🗄️  MySQL Database Setup for aleksandar.pro", 'blue')
    print("")
    print_colored("💡 Tip: You can paste passwords - they won't be visible", 'yellow')
    print("")
    
    # Get database name
    db_name = input("Database name [aleksandar_pro]: ").strip() or "aleksandar_pro"
    
    # Get username
    db_user = input("Database username [aleksandar_user]: ").strip() or "aleksandar_user"
    
    # Get password (with confirmation)
    print("")
    print_colored("📝 Enter database password (you can paste it):", 'yellow')
    db_pass = get_password_input("Database password", confirm=True)
    
    # Get MySQL root password
    print("")
    print_colored("🔐 Enter MySQL root password:", 'yellow')
    root_pass = getpass.getpass("MySQL root password: ")
    
    if not root_pass:
        print_colored("❌ MySQL root password cannot be empty!", 'red')
        sys.exit(1)
    
    # Build SQL commands
    sql_commands = f"""
CREATE DATABASE IF NOT EXISTS `{db_name}` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '{db_user}'@'localhost' IDENTIFIED BY '{db_pass}';

GRANT ALL PRIVILEGES ON `{db_name}`.* TO '{db_user}'@'localhost';

FLUSH PRIVILEGES;

USE `{db_name}`;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    slug VARCHAR(255) UNIQUE,
    avatar VARCHAR(255),
    newsletter BOOLEAN DEFAULT FALSE,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT '✅ Database created successfully!' AS message;
SELECT '✅ User created successfully!' AS message;
SELECT '✅ Users table created successfully!' AS message;
"""
    
    # Execute
    print("")
    print_colored("🔄 Creating database and user...", 'yellow')
    print("")
    
    success, output = execute_mysql_commands(sql_commands, root_pass)
    
    if success:
        print_colored("✅ Database setup completed successfully!", 'green')
        print("")
        print_colored("📋 Database Information:", 'blue')
        print(f"   Database: {db_name}")
        print(f"   Username: {db_user}")
        print(f"   Password: [hidden]")
        print("")
        print_colored("💡 Next steps:", 'yellow')
        print("   1. Update .env file with these credentials")
        print("   2. Run: php test-pdo.php")
        print("")
    else:
        print_colored(f"❌ Database setup failed!", 'red')
        print(f"   Error: {output}")
        print("")
        sys.exit(1)

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("\n")
        print_colored("❌ Cancelled by user", 'red')
        sys.exit(1)

