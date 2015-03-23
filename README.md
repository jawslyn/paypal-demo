# paypaldemo
##Source code for website that allows a user to purchase a product using PayPal’s Express Checkout.
[Demo Website](http://jocelynongyt.uphero.com/test/products)

###product.php:
* shows all the products with cart
* details are retrieved from database

###update-cart.php:
* runs when buyer adds item to cart
* redirects to product page

###view-cart.php:
* page to view current items in cart
* buyer is able to choose whether to use PayPal's Express Checkout or proceed with normal checkout(currently not implemented)

###process.php:
* runs when buyer clicks on button for Express Checkout
* invokes SetExpressCheckout which directs buyer to PayPal to login if successful
* receives token and execute GetExpressCheckout to obtain Payer ID and information about buyer
* prints relevant information for buyer to see and lets buyer confirm the details (or cancel payment)

###confirm.php:
* execute DoExpressCheckout after buyer clicks confirm payment (transaction will be completed)
* present a successful message for buyer

###dbdetails.php:
* information to access database

###ppdetails.php:
* information to access PayPal account

###class-paypal.php:
* class to invoke the different methods for Express Checkout (SetExpressCheckout, GetExpressCheckout, DoExpressCheckout)
