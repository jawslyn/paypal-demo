<?php

/*
 Template Name: Cart Page
*/

session_start();
include_once("dbdetails.php");

get_header(); 

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>View shopping cart</title>
<!--<link href="/style/style.css" rel="stylesheet" type="text/css"> !-->
</head>
<body>
<div id="products-wrapper">
 <h1>View Cart 
 	<form action="products">
	<input type="submit" value="Back To Products">
    </form></h1>
 <div class="view-cart">
 	<?php
    $current_url = base64_encode($url="http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	if(isset($_SESSION["products"]))
    {
	    $total = 0;
		echo '<form method="post" action="process">';
		echo '<ul>';
		$cart_items = 0;
		foreach ($_SESSION["products"] as $cart_itm)
        {
           $product_code = $cart_itm["code"];
		   $results = $mysqli->query("SELECT product_name,product_desc, price, product_img_src FROM products WHERE product_code='$product_code' LIMIT 1");
		   $obj = $results->fetch_object();
		   
		    echo '<li class="cart-itm">';
			echo '<span class="remove-itm"><a href="update-cart.php?removep='.$cart_itm["code"].'&return_url='.$current_url.'">&times;</a></span>';
			echo '<div class="p-price">'.$currency.$obj->price.'</div>';
			echo '<img src="'.$obj->product_img_src.'" height="100">';
            echo '<div class="product-info">';
			echo '<h3>'.$obj->product_name.' (Code :'.$product_code.')</h3> ';
            echo '<div class="p-qty">Qty : '.$cart_itm["qty"].'</div>';
            echo '<div>'.$obj->product_desc.'</div>';
			echo '</div>';
            echo '</li>';
			$subtotal = ($cart_itm["price"]*$cart_itm["qty"]);
			$total = ($total + $subtotal);

			echo '<input type="hidden" name="item_name['.$cart_items.']" value="'.$obj->product_name.'" />';
			echo '<input type="hidden" name="item_code['.$cart_items.']" value="'.$product_code.'" />';
			echo '<input type="hidden" name="item_desc['.$cart_items.']" value="'.$obj->product_desc.'" />';
			echo '<input type="hidden" name="item_qty['.$cart_items.']" value="'.$cart_itm["qty"].'" />';
			$cart_items ++;
			
        }
    	echo '</ul>';
		echo '<span class="check-out-txt">';
		echo '<strong><font size="3pt">Total : '.$currency.number_format($total, 2).'</font></strong><p>';
		echo '</span>';
		echo '</div>';

		echo '<div class="button">';
		echo '<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif" align="left" style="margin-right:7px;" alt="submit">';
		echo '<strong> -OR- </strong>';
		echo '</form>';
		echo '<input type="submit" name="checkout" value="Proceed To Checkout">';
		echo '</div>';
		
    }else{
		echo 'Your Cart is empty';
		echo '</div>';
	}
	
    ?>
</div>
</body>
</html>

<?php get_footer(); ?>
