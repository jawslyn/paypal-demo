<?php
include_once("dbdetails.php");
include_once("ppdetails.php");
include_once("class-paypal.php");

session_start();

$paypalmode = ($PayPalMode=='sandbox') ? '.sandbox' : '';

if($_POST) //Received POST from product list page.
{
	$ShippinCost 		= 3.00; 
	
	$paypal_data ='';
	$ItemTotalPrice = 0;
	
    foreach($_POST['item_name'] as $key=>$itmname)
    {
        $product_code 	= filter_var($_POST['item_code'][$key], FILTER_SANITIZE_STRING); 
		
		$results = $mysqli->query("SELECT product_name, product_desc, price FROM products WHERE product_code='$product_code' LIMIT 1");
		$obj = $results->fetch_object();
		
        $paypal_data .= '&L_PAYMENTREQUEST_0_NAME'.$key.'='.urlencode($obj->product_name);
        $paypal_data .= '&L_PAYMENTREQUEST_0_NUMBER'.$key.'='.urlencode($_POST['item_code'][$key]);
        $paypal_data .= '&L_PAYMENTREQUEST_0_AMT'.$key.'='.urlencode($obj->price);		
		$paypal_data .= '&L_PAYMENTREQUEST_0_QTY'.$key.'='. urlencode($_POST['item_qty'][$key]);
        
		//item price X quantity
        $subtotal = ($obj->price*$_POST['item_qty'][$key]);
		
        //total price
        $ItemTotalPrice = $ItemTotalPrice + $subtotal;
		
		//create items for session
		$paypal_product['items'][] = array('itm_name'=>$obj->product_name,
											'itm_price'=>$obj->price,
											'itm_code'=>$_POST['item_code'][$key], 
											'itm_qty'=>$_POST['item_qty'][$key]
											);
    }
				
	//grand total including all tax, insurance, shipping cost and discount
	$GrandTotal = ($ItemTotalPrice + $TotalTaxAmount + $HandalingCost + $InsuranceCost + $ShippinCost + $ShippinDiscount);
	
								
	$paypal_product['assets'] = array('tax_total'=>$TotalTaxAmount, 
								'handaling_cost'=>$HandalingCost, 
								'insurance_cost'=>$InsuranceCost,
								'shippin_discount'=>$ShippinDiscount,
								'shippin_cost'=>$ShippinCost,
								'grand_total'=>$GrandTotal);
	
	//create session array for later use
	$_SESSION["paypal_products"] = $paypal_product;
	
	//parameters for SetExpressCheckout, which will be sent to PayPal
	$padata = 	'&METHOD=SetExpressCheckout'.
				'&RETURNURL='.urlencode($PayPalReturnURL ).
				'&CANCELURL='.urlencode($PayPalCancelURL).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").$paypal_data.				
				'&NOSHIPPING=0'. 
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
				'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
				'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
				'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
				'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
				'&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode).
				'&LOCALECODE=GB'. 
				'&CARTBORDERCOLOR=FFFFFF'. 
				'&ALLOWNOTE=1';
		
		//execute the "SetExpressCheckOut" method to obtain paypal token
		$paypal= new MyPayPal();
		echo 'BEFORE SetExpressCheckout';
		$httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);
		
		//Respond according to message we receive from Paypal
		if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
		{
				//Redirect user to PayPal store with Token received.
			 	$paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
				header('Location: '.$paypalurl);
		}
		else
		{
			//Show error message
			echo '<div style="color:red"><b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
			echo '<pre>';
			print_r($httpParsedResponseAr);
			echo '</pre>';
		}

}

//Paypal redirects back to this page using ReturnURL, receives TOKEN and Payer ID
if(isset($_GET["token"]) && isset($_GET["PayerID"]))
{
	$token = $_GET["token"];
	$payer_id = $_GET["PayerID"];
	$count = $_SESSION["count"];

	$_SESSION["token"] = $token;
	$_SESSION["payer_id"] = $payer_id;
	
	//get session variables
	$paypal_product = $_SESSION["paypal_products"];
	$paypal_data = '';
	$ItemTotalPrice = 0;

    foreach($paypal_product['items'] as $key=>$p_item)
    {		
    	$count++;
		$paypal_data .= '&L_PAYMENTREQUEST_0_QTY'.$key.'='. urlencode($p_item['itm_qty']);
        $paypal_data .= '&L_PAYMENTREQUEST_0_AMT'.$key.'='.urlencode($p_item['itm_price']);
        $paypal_data .= '&L_PAYMENTREQUEST_0_NAME'.$key.'='.urlencode($p_item['itm_name']);
        $paypal_data .= '&L_PAYMENTREQUEST_0_NUMBER'.$key.'='.urlencode($p_item['itm_code']);
        
		//item price X quantity
        $subtotal = ($p_item['itm_price']*$p_item['itm_qty']);
		
        //total price
        $ItemTotalPrice = ($ItemTotalPrice + $subtotal);

    }

	$padata = 	'&TOKEN='.urlencode($token).
				'&PAYERID='.urlencode($payer_id).
				'&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").$paypal_data.
				'&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
				'&PAYMENTREQUEST_0_TAXAMT='.urlencode($paypal_product['assets']['tax_total']).
				'&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($paypal_product['assets']['shippin_cost']).
				'&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($paypal_product['assets']['handaling_cost']).
				'&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($paypal_product['assets']['shippin_discount']).
				'&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($paypal_product['assets']['insurance_cost']).
				'&PAYMENTREQUEST_0_AMT='.urlencode($paypal_product['assets']['grand_total']).
				'&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($PayPalCurrencyCode);

	$paypal= new MyPayPal();
	$httpParsedResponseAr = $paypal->PPHttpPost('GetExpressCheckoutDetails', $padata, $PayPalApiUsername, $PayPalApiPassword, $PayPalApiSignature, $PayPalMode);

	if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"])) 
	{
		get_header();
		echo '<div id="products-wrapper">';
 		echo '<h1><font size="6pt">Confirmation</font></h1>';
 		echo '<div class="view-cart">';
 			echo '<div class="cart-itm">';
 			$buyerName = urldecode($httpParsedResponseAr["FIRSTNAME"]).' '.urldecode($httpParsedResponseAr["LASTNAME"]);
			$buyerEmail = urldecode($httpParsedResponseAr["EMAIL"]);
			$addstreet = urldecode($httpParsedResponseAr["SHIPTOSTREET"]);
			$addcity = urldecode($httpParsedResponseAr["SHIPTOCITY"]);
			$addzip = urldecode($httpParsedResponseAr["SHIPTOZIP"]);
			echo '<font size="3pt">';
			echo '<strong>Name:</strong> '; echo $buyerName; echo '<p>';
			echo '<strong>Shipping Details:</strong> '; 
			echo '<p style="text-indent: 5em;">'; echo $addstreet; echo '</p>';
			echo '<p style="text-indent: 5em;">'; echo $addcity; echo '</p>';
			echo '<p style="text-indent: 5em;">'; echo $addzip; echo '</p>';

			echo '<strong>Orders:</strong>';
			echo '<br>----------------------------------------------------------------------------------------------<br>';
			foreach($paypal_product['items'] as $key=>$p_item)
    		{	
				echo '<p style="text-indent: 5em;"><strong>Product Name: </strong>'; echo $p_item['itm_name']; echo '</p>';
				echo '<p style="text-indent: 5em;"><strong>Product Code: </strong>'; echo $p_item['itm_code']; echo '</p>';
				echo '<p style="text-indent: 5em;"><strong>Price: $</strong>'; echo $p_item['itm_price']; echo '</p>';
				echo '<p style="text-indent: 5em;"><strong>Quantity: </strong>'; echo $p_item['itm_qty']; echo '</p>';

				$subtotal = ($p_item['itm_price']*$p_item['itm_qty']);
				echo '<strong>Subtotal: $</strong>'; echo number_format($subtotal,2); echo '</p>';

				echo '----------------------------------------------------------------------------------------------';

		    }
		    echo '<br><br>';

		    echo '<strong>Total: $</strong>'; echo urldecode($httpParsedResponseAr["ITEMAMT"]); echo '<br>';
		    echo '<strong>Shipping Fee: $</strong>'; echo urldecode($httpParsedResponseAr["SHIPPINGAMT"]); echo '<br>';
		    echo '<strong>Amount Payable: $</strong>'; echo urldecode($httpParsedResponseAr["AMT"]);

			echo '</font>';
			echo '</div>';


			echo '<div class="buttonconfirm">';
		   	echo '<form method="post" action="confirm">';
		   		echo '<input type="submit" name="confirm" value="Confirm Payment">';
           	echo '</form>';

           	echo '<form action="products">';
		   		echo '<input type="submit" value="Cancel">';
           	echo '</form>';
           	echo '</div>';
		

		get_footer();
		//echo '<pre>';
		//print_r($httpParsedResponseAr);
		//echo '</pre>';
	} else  {
		echo '<div style="color:red"><b>GetTransactionDetails failed:</b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
		echo '<pre>';
		print_r($httpParsedResponseAr);
		echo '</pre>';

	}
}
?>