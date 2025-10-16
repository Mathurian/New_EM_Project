# Contest Judge (PHP + SQLite for Apache)

## Features
- Multiple contests spanning multiple days (start/end dates)
- Categories and subcategories per contest
- Multiple contestants and judges per subcategory
- Define criteria per subcategory
- Scoring per judge per criterion per contestant
- Results aggregation per subcategory

## Local Run (PHP built-in server)
```bash
cd /path/to/New_EM_Project
php -S localhost:8000 -t public
```
Open http://localhost:8000

## Ubuntu + Apache Deployment
1. Install dependencies
```bash
sudo apt update
sudo apt install -y apache2 libapache2-mod-php php php-sqlite3
```
2. Deploy code (example path `/var/www/contest-judge`)
```bash
sudo mkdir -p /var/www/contest-judge
sudo rsync -a --delete /path/to/New_EM_Project/ /var/www/contest-judge/
sudo chown -R www-data:www-data /var/www/contest-judge/app/db
```
3. Create Apache site
```bash
sudo tee /etc/apache2/sites-available/contest-judge.conf >/dev/null <<'VHOST'
<VirtualHost *:80>
	ServerName your_domain_or_ip
	DocumentRoot /var/www/contest-judge/public
	<Directory /var/www/contest-judge/public>
		AllowOverride All
		Require all granted
	</Directory>
	ErrorLog ${APACHE_LOG_DIR}/contest-judge-error.log
	CustomLog ${APACHE_LOG_DIR}/contest-judge-access.log combined
</VirtualHost>
VHOST
```
4. Enable site and rewrite
```bash
sudo a2enmod rewrite
sudo a2ensite contest-judge
sudo systemctl reload apache2
```
5. Visit http://your_domain_or_ip

## Notes
- Database file: `app/db/contest.sqlite` auto-created; ensure `www-data` can write to `app/db`.
- Login with: 

admintester2 / Admintester123! - admin user

judgetester2 / Judgetester123! - judge user

emceetester2 / Emceetester123! - emcee user 
