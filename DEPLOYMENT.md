# Vercel preview deployment (Laravel + MySQL)

This repository is prepared to run Laravel 12 in a Vercel container using FrankenPHP. The Vercel Hobby plan is free, but its terms limit it to personal, non-commercial use. A portal used to run a school is not an appropriate Hobby production deployment. Use this setup only for a personal preview with synthetic records; do not enter real student, parent, or staff data.

Vercel containers are stateless. Vercel does not currently provide MySQL in its Marketplace integrations, so a separate database provider is required for a functional preview. Keep its credentials in Vercel environment variables. Vercel container functions do not have static outbound IPs, so an external database cannot be restricted to a fixed Vercel IP range. This is another reason to keep the free deployment limited to synthetic demo data.

## Deployment files

- `Dockerfile.vercel` builds the Vite assets, installs Laravel's production PHP dependencies, enables `pdo_mysql`, and serves Laravel with FrankenPHP.
- `Caddyfile` serves only Laravel's `public` directory and forwards app routes to `public/index.php`.
- `vercel.json` routes the Vercel project to the container service.
- `.dockerignore` prevents local environment files, dependencies, and database exports from entering the image.

## Connect the repository

1. Sign in to Vercel and import `abdikarim998/SchoolDesk` from GitHub. Grant the GitHub app access to this repository only.
2. Leave the account on the free Hobby plan. Do not start a Pro trial or add billing details.
3. Create a MySQL preview database with the provider. Use a unique, strong password and synthetic records only. The free Aiven MySQL tier, for example, is limited to 1 GB and may power off when unused; it is suitable only for a prototype.
4. Add the following variables to Vercel Project Settings → Environment Variables for Production. Use the values supplied by the database provider and generate a new, stable `APP_KEY` for this hosted app. Download that provider's public CA certificate and base64-encode it as `MYSQL_CA_CERT_BASE64`; keep the same CA contents in the GitHub repository secret of the same name. In PowerShell, encode a downloaded `ca.pem` with `[Convert]::ToBase64String([IO.File]::ReadAllBytes('C:\path\to\ca.pem'))`.

   ```text
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<new hosted-app key; keep private>
   APP_URL=https://<your-project>.vercel.app
   APP_TIMEZONE=Africa/Nairobi
   LOG_CHANNEL=stderr
   DB_CONNECTION=mysql
   DB_HOST=<database host>
   DB_PORT=<database port>
   DB_DATABASE=<database name>
   DB_USERNAME=<database user>
   DB_PASSWORD=<database password>
   MYSQL_CA_CERT_BASE64=<base64 encoded MySQL provider CA certificate>
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=sync
   ```

   The container entrypoint decodes this public CA certificate into `/tmp` and configures PDO to verify the MySQL server certificate. Do not disable certificate verification to work around TLS setup.

5. In GitHub repository Settings → Secrets and variables → Actions, add these repository secrets: `APP_KEY`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `MYSQL_CA_CERT_BASE64`. These are used by the schema migration workflow; use the same CA certificate for Vercel and GitHub.
6. In GitHub, open Actions → **Migrate Vercel database** → **Run workflow**. This runs the committed Laravel migrations against the hosted MySQL service. Do not run migrations automatically when a Vercel container starts: multiple instances can start concurrently.
7. Deploy the app from Vercel and check the deployment logs and `/up`. Add a fresh administrator through the `school:create-admin` command from a trusted PHP environment; never enable the development seeder on a public deployment.

## Data and runtime limits

- The deploy uses migrations only; it does not upload the local `.env`, database, or seeded student roster.
- Container files and local uploads do not persist across Vercel instances. Configure external object storage before relying on uploaded files.
- The free database and Vercel Hobby quotas can pause or power off services. This setup does not provide a school-grade availability or backup guarantee.
- For real school operations, use a hosting plan and database arrangement approved for the school's use, with access controls, verified TLS, backups, and an agreed data-retention policy.
