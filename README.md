## What does it do:
It integrates PayPal and Stripe API's for online payments and subscriptions based online payments with a simple UI and its flexible to be used with other payments platforms. This web app also handles Strong Customer Authentication (SCA) and implments 3D Secure for Stripe.  

## Aim:
Copy the code you need from this project and integrate it in your own.

## How to set up this project to test it out:
- First rename the name of the `.env.example` file to `.env`
- Next create a relational database and assign its name to the `DB_DATABASE` key in `.env` file
- Next take your Paypal and Stripe public key & secret key and assign it to `PAYPAL_CLIENT_ID` , `PAYPAL_CLIENT_SECRET`, `STRIPE_KEY`, `STRIPE_SECRET` keys respectively in `.env` file.
- Next create two subscriptions plans of monthly and yearly in PayPal with pricing of US$12.00 / month and US$99.99 / year then add there Plan ID to `PAYPAL_MONTHLY_PLAN` and `PAYPAL_YEARLY_PLAN` respectively in `.env` file. 
- Next create a product with two pricing i.e monthly and yearly in Stripe with pricing of US$12.00 / month and US$99.99 / year then and add there API ID to `STRIPE_MONTHLY_PLAN` and `STRIPE_YEARLY_PLAN` plan respectively in `.env` file.
- Then run the `php artisan migrate --seed` command in your terminal to migrate the tables into your database and create the according records for the Payment Platforms, Currencies and Plans.
- Then you are done, open the application in your browser and test it out.
**Remember to use PayPal Sandbox Accounts and Stripe test data to test the application**