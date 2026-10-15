# Public deployment (Railway + MySQL)

This project can run as a public Laravel application with a private MySQL service on Railway's Free plan. The app service gets a Railway `*.up.railway.app` address with HTTPS. The address is usable while the service is active.

## Cost

Railway Free costs **US$0/month** and includes **US$1 of resource usage per month**. A new account may first receive a one-time US$5 trial credit for up to 30 days; the recurring Free-plan credit is still only US$1/month. If the app and MySQL use more than the free allowance, Railway can stop the services until the next billing cycle. Stay on Free and do not add a paid plan or committed spend. Railway's configurable hard usage limit has a US$10 minimum, so it cannot be used as a US$1 cap. This is suitable for a prototype, but it cannot guarantee that the school portal stays online all month. See [Railway's current plan limits](https://docs.railway.com/pricing/plans) and [cost controls](https://docs.railway.com/pricing/cost-control).

## Deploy the app

1. Create/sign in to a Railway account and stay on the **Free** plan.
2. In the project folder, install the Railway CLI, sign in, create a project, and upload this app:

   ```powershell
   npm install --global @railway/cli
   railway login
   railway init
   railway up
   ```

   The CLI uploads the local project folder and respects `.gitignore`. `.env` files and SQL/database dump files are excluded.
3. In the Railway project canvas, add a **MySQL** service. Keep its database network private; only the Laravel service needs to connect to it.
4. In the Laravel service, set the variables below. In Railway's variable editor, reference the MySQL service variables; if the service is named `MySQL`, the references look like `${{MySQL.MYSQLHOST}}`.

   ```text
   APP_ENV=production
   APP_DEBUG=false
   APP_TIMEZONE=Africa/Nairobi
   APP_KEY=<generate a new key for this hosted app>
   APP_URL=https://<the-generated-Railway-domain>
   LOG_CHANNEL=stderr
   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   ```

   Generate a fresh key with `php artisan key:generate --show` in a trusted local terminal and paste it into Railway's private variable editor. Never reuse the local app key or publish it in chat or source control. Keep `APP_DEBUG=false` in production.

5. Set the Laravel service's **Pre-deploy Command** to:

   ```text
   php artisan migrate --force
   ```

   This creates the schema in the hosted MySQL database during deployment. Do not use `migrate --seed` for production: the development seeder creates public demo accounts with a shared, known password and a synthetic student roster.
6. In the Laravel service settings, enable **Serverless** so the app sleeps when nobody is using it. MySQL must remain available for data access, so its usage can still consume the monthly credit. Leave the account on Free; a US$1 hard limit is not available. Let Railway deploy the app. Its Laravel builder detects the framework and runs it with PHP-FPM and Caddy. In Settings → Networking, generate a Railway domain. Set `APP_URL` to that exact HTTPS address and the health check path to `/up`, then redeploy.
7. After the first successful migration, open the Laravel service shell and run:

   ```text
   php artisan school:create-admin
   ```

   The command asks for the administrator name and email, then prompts for a password without displaying it. Choose a unique password of at least 12 characters. Sign in at the generated domain and add real school accounts through the admin area.
8. Keep the MySQL service's public/TCP access disabled. Before adding any real school records, make a separate database backup and confirm it can be restored; the Free plan's limited allowance and service interruptions make it unsuitable as the only copy of school data.

## Data handling

- This deployment starts with an empty database and the migrations only. It does not upload the local database or seeded demo data.
- Do not put real student data in this Free-plan deployment. Use synthetic records until the school chooses reliable paid hosting and an approved storage arrangement.
- Do not share Railway database credentials, `APP_KEY`, or administrator passwords. Store them only as Railway variables or in a trusted password manager.

## Rollback and ongoing changes

The Railway CLI can upload code changes with `railway up`. Database migrations should be backward-compatible with the running app, and MySQL backups should be checked regularly. The Railway MySQL service is private by default; Railway documents its variables and backup controls in the [MySQL guide](https://docs.railway.com/databases/mysql).
