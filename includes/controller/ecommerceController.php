<?php 
session_start();
include '../dbConnect.php';	
include("../dbClass.php");

$dbClass = new dbClass;
extract($_REQUEST);

switch ($q){
    case "addToCart":
        //$_SESSION['cart'] = "";
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else 													 $cart = $_SESSION['cart'];

        $cart_key = $item_id.'_'.$rate_id;
        if (array_key_exists($cart_key,$cart)){
            $updatable_item = $cart[$cart_key];

            if($quantity==0){
                unset($cart[$cart_key]);
            }
            else{
                $discounted_rate = $updatable_item['discounted_rate'];
                $total_quantity = ($quantity+$updatable_item['quantity']);
                $cart[$cart_key]['quantity'] 	  = $total_quantity;
                $toal_amount = $total_quantity*$updatable_item['discounted_rate'];
                $cart[$cart_key]['item_total'] = $toal_amount;
                $_SESSION['cart'] = $cart;
            }
        }
        else{
            $selected_item = array();
            $item_details_info = $dbClass->getSingleRow("select pr.item_id, pim.item_image, prt.rate, prt.discounted_rate, prt.unit_id,  s.name as size_name, pr.name as item_name
														from items pr
														left join item_image pim on pim.item_id=pr.item_id
														left join item_rate prt on   prt.item_id=pr.item_id
														left join size s on s.id=prt.size_id
														where  pr.item_id=$item_id and prt.id=$rate_id
														group by pr.item_id,  prt.id");
            $selected_item['item_id'] = $item_id;
            $selected_item['cart_key'] = $cart_key;
            $selected_item['item_name'] = $item_details_info['item_name'];
            $selected_item['unit_id'] = $item_details_info['unit_id'];
            $selected_item['item_image'] = $item_details_info['item_image'];
            $selected_item['orignal_rate'] = $item_details_info['rate'];
            $selected_item['discounted_rate'] = $item_details_info['discounted_rate'];
            $selected_item['size'] = $item_details_info['size_name'];
            $selected_item['quantity'] = $quantity;
            $selected_item['item_total'] = ($item_details_info['discounted_rate']*$quantity);

            $cart[$item_id.'_'.$rate_id]=$selected_item;
            $_SESSION['cart'] = $cart;
        }
        $data['records'] = $cart;
        echo json_encode($data);
        break;


    case "viewCartSummery":
        //echo 1; die;
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else 													 $cart = $_SESSION['cart'];
        $data['records'] = $cart;
        echo json_encode($data);
    break;

    case "viewPriceSummery":
        $tax = $dbClass->getSingleRow("Select tax_type, tax_amount, tax_enable from general_settings where id=1");
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else 													 $cart = $_SESSION['cart'];
        $total_price=0;
        $data = [];
        foreach ($cart as $items){
            $total_price+=($items['discounted_rate']* $items['quantity']);
        }
        $data['total_price']= $total_price;

        if(isset($_SESSION['total_discounted_amount'])){

            if(isset($_SESSION['min_order_amount']) && $total_price>=$_SESSION['min_order_amount']){
                $data['discount'] = $_SESSION['total_discounted_amount'];
                $data['discounted_price'] = $total_price-$data['discount'];

            }
            else{
                $data['discounted_price']=$total_price;
                $data['discount'] = 0;
            }
        }
        else{
            $data['discounted_price']=$total_price;
            $data['discount'] = 0;
        }
        //echo $data['discount']; die;

        if ($tax['tax_enable']==1){
            if($tax['tax_type']==1){
                $data['tax_amount']= $tax['tax_amount'];
            }
            else{
                $data['tax_amount']= $data['discounted_price']*$tax['tax_amount']/100;
            }
            $data['discounted_price']=$data['discounted_price']+$data['tax_amount'];
        }
        else{
            $data['tax_amount']= 0;
        }
        echo  json_encode($data);
    break;

    case "removeFromCart":
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else 													 $cart = $_SESSION['cart'];

        //echo $cart[$cart_key]; die;

        if (array_key_exists($cart_key,$cart)){
            unset($cart[$cart_key]);
        }
        $_SESSION['cart']= $cart;
        $data['records'] = $cart;
        echo json_encode($data);
    break;


    case "update_cart":
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else{
            $cart = $_SESSION['cart'];

            foreach($cart_key as $key=>$cart_key){
                //echo $cart_key;die;

                if (array_key_exists($cart_key,$cart)){
                    $updatable_item = $cart[$cart_key];

                    if($quantity==0){
                        unset($cart[$cart_key]);
                    }
                    else{
                        //echo $updatable_item['discounted_rate'];die;
                        $discounted_rate = $updatable_item['discounted_rate'];
                        $cart[$cart_key]['quantity']  = $quantity[$key];
                        $toal_amount = $quantity[$key]*$updatable_item['discounted_rate'];
                        $cart[$cart_key]['item_total'] = $toal_amount;

                        $_SESSION['cart'] = $cart;

                    }
                }
            }
            echo json_encode($_SESSION['cart']);die;
            echo 1;
        }
    break;


    case "apply_cupon":
        //var_dump($_SESSION);die;
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']))$cart = array();
        else{
            $cart = $_SESSION['cart'];
            // get the total cart amount
            $total_cart_amount = 0;
            foreach($cart as $key=>$item){
                $total_cart_amount += $item['item_total'];
            }
            $cupon_amount = 0;
            $date = date("Y-m-d");
            //echo $date; die;
            if(isset($_SESSION['customer_id']) && $_SESSION['customer_id'] != ""){
                //echo("select c_type,amount from cupons where status=1 and ((cupon_no='$cupon_code' and customer_id = ".$_SESSION['customer_id'].") or cupon_no='$cupon_code' and customer_id is null) and (DATE_FORMAT(start_date, '%Y-%m-%d') <= '$date' AND DATE_FORMAT(end_date, '%Y-%m-%d') >= '$date')");die;

                $cupon_info = $dbClass->getSingleRow("select c_type,amount,min_order_amount from cupons where status=1 and ((cupon_no='$cupon_code' and customer_id = ".$_SESSION['customer_id'].") or cupon_no='$cupon_code' and customer_id is null) and (DATE_FORMAT(start_date, '%Y-%m-%d') <= '$date' AND DATE_FORMAT(end_date, '%Y-%m-%d') >= '$date')");
            }
            else{
               // echo ("select c_type,amount from cupons where status=1 and cupon_no='$cupon_code' and customer_id is null and(DATE_FORMAT(start_date, '%Y-%m-%d') <= '$date' AND DATE_FORMAT(end_date, '%Y-%m-%d') >= '$date')"); die;
                $cupon_info = $dbClass->getSingleRow("select c_type,amount,min_order_amount from cupons where status=1 and cupon_no='$cupon_code' and customer_id is null and(DATE_FORMAT(start_date, '%Y-%m-%d') <= '$date' AND DATE_FORMAT(end_date, '%Y-%m-%d') >= '$date')");
            }
            //echo($cupon_info);die;
            if($cupon_info){
                //echo 1;die;
                if($cupon_info['c_type']==1 && $cupon_info['min_order_amount']<=$total_cart_amount) {// flat amount
                    $cupon_amount = $cupon_info['amount'];
                    $min_order_amount = $cupon_info['min_order_amount'];
                }
                else if($cupon_info['c_type']==2 && $cupon_info['min_order_amount']<=$total_cart_amount){ // % amount
                    $cupon_percent = $cupon_info['amount'];
                    $cupon_amount = ($total_cart_amount*$cupon_percent)/100;
                    $min_order_amount = $cupon_info['min_order_amount'];

                }
                else{
                    $cupon_amount = 0;
                    $min_order_amount = $cupon_info['min_order_amount'];
                }
                //echo $total_cart_amount; die;
                $_SESSION['total_discounted_amount'] = $cupon_amount;
                $_SESSION['cupon_code'] = $cupon_code;
                $_SESSION['min_order_amount'] = $min_order_amount;
                //$data['total_discounted_amount'] = $cupon_amount;
                //echo json_encode($data);
                echo 1;
            }
            else{
                unset($_SESSION['total_discounted_amount']);
                unset($_SESSION['cupon_code']);
                echo 2; // invalid cupon
            }
        }

    break;



    case "checkout":

       // echo $loyalty_deduct; die;
        if(!isset($_SESSION['cart']) || empty($_SESSION['cart']) || empty($_SESSION['customer_id']) || $_SESSION['customer_id']=="" ){echo "0"; die;}
        else 	$cart = $_SESSION['cart'];
        //echo 333;die;
        //echo $_SESSION['customer_id'];die;

        //------------ generate invoice no  -------------------
        $c_y_m = date('my');
        $last_invoice_no = $dbClass->getSingleRow("SELECT max(RIGHT(invoice_no,5)) as invoice_no FROM order_master");

        if($last_invoice_no == null){
            $inv_no = '00001';
        }
        else{
            $inv_no = $last_invoice_no['invoice_no']+1;
        }

        $str_length = 5;
        $str = substr("00000{$inv_no}", -$str_length);
        //echo 333;die;

        $invoice_no = "CnC$c_y_m$str";
        //-----------------------------------------------------

        $secial_notes	 = htmlspecialchars($secial_notes,ENT_QUOTES);

        $columns_value = array(
            'customer_id'=>$_SESSION['customer_id'],
            'delivery_date'=>$pickup_date_time,
            'delivery_type'=>1,
            'remarks'=>$secial_notes,
            'order_status'=>1,
            'invoice_no'=>$invoice_no,
            'payment_method' =>$payment_method,
            'total_order_amt'=>$total_order_amt,
            'tax_amount'=>$tax_amount,
            'total_paid_amount'=>$total_paid_amount,
            'loyalty_point'=>$loyalty_point

        );
        //echo 333;die;

//echo $columns_value['total_paid_amount']; die;
        if(isset($_SESSION['cupon_code']) || !empty($_SESSION['cupon_code'])){
            $columns_value['cupon_id'] 			= $_SESSION['cupon_code'];
            $columns_value['discount_amount']	= $_SESSION['total_discounted_amount'];

            //echo $_SESSION['total_discounted_amount']; die;

        }

        $paid = 0; // not paid
       /* if(isset($_POST['payment_type']) && ($_POST['payment_type'] == 1 || $_POST['payment_type'] == 2)){
            $paid = 1;
            $columns_value['payment_status'] 		= 2;
            $columns_value['payment_method'] 		= $payment_type;
            $columns_value['payment_reference_no']  = $reference_no;
            $columns_value['total_paid_amount']  	= $grand_total;
        }
        else{
            $columns_value['payment_status'] 		= 1;
        }*/
        //echo 333;die;

       if($total_paid_amount){

               $columns_value['payment_status'] 		= 2;
               $columns_value['payment_reference_no']   = 1;

       }else{
           $columns_value['payment_status'] 		= 1;
       }

        $return_master = $dbClass->insert("order_master", $columns_value);
        //echo $return_master; die;
        if($return_master){
            //echo 545; die;

            foreach($cart as $key=>$item){
                $cart_key_arr = explode('_',$key);
                $item_size_rate_id = $cart_key_arr[1];
                $columns_value = array(
                    'order_id'=>$return_master,
                    'item_id'=>$item['item_id'],
                    'quantity'=>$item['quantity'],
                    'unit_id'=>$item['unit_id'],
                    'item_rate_id'=>$item_size_rate_id,
                    'item_rate'=>$item['discounted_rate']
                );
                $return_details = $dbClass->insert("order_details", $columns_value);
            }
            if($return_details){

                    $customer_loyalty_point=  $dbClass->getSingleRow("SELECT loyalty_points from customer_infos where customer_id=".$_SESSION['customer_id']);
                    //echo $customer_loyalty_point; die;
                    $new_loyalty_point=$customer_loyalty_point['loyalty_points']-$loyalty_deduct+$loyalty_point;
                    $value_ar= array(
                        'loyalty_points'=>$new_loyalty_point
                    );
                    $condition_ar= array(
                        'customer_id'=>$_SESSION['customer_id']
                    );
                    $customer_loyalty_update=  $dbClass->update("customer_infos",$value_ar,$condition_ar);

                //echo 11; die;

                $cart = array();
                unset($_SESSION['total_discounted_amount']);
                unset($_SESSION['cupon_code']);
                unset($_SESSION['min_order_amount']);
                $_SESSION['cart'] = $cart;
                $_SESSION['latest_order_id'] = $return_master;
                $_SESSION['payment'] 		 = $paid;



                // send mail to customer account
                if(isset($_SESSION['customer_email'])){
                    $customer_email = $_SESSION['customer_email'];
                    if($customer_email){
                        $sql = "SELECT m.order_id, m.customer_id, 
                                c.full_name customer_name, d.item_id, c.contact_no customer_contact_no, 
                                c.address customer_address, 
                                GROUP_CONCAT(ca.name,' >> ',ca.id,'#',ca.id,'#',p.name,' (',ca.name,' )','#',p.item_id,'#',s.name,'#',d.item_rate_id,'#',d.item_rate,'#',d.quantity) order_info,
                                m.order_date, m.delivery_date, m.delivery_type,m.delivery_charge, m.discount_amount, m.total_paid_amount,
                                m.outlet_id, m.address, m.delivery_charge_id, m.tax_amount,
                                m.remarks, m.order_status, m.payment_status, m.payment_method, 
                                m.payment_reference_no, m.invoice_no, m.total_order_amt,
                                case payment_status when payment_status=1 then 'Not Paid' else 'Paid' end paid_status, 
                                case payment_method when payment_method=1 then 'bKash' when payment_method=2 then 'Rocket'  else 'Cash On Delivery'  end payment_method
                                FROM order_master m
                                LEFT JOIN order_details d ON d.order_id = m.order_id
                                LEFT JOIN customer_infos c ON c.customer_id = m.customer_id
                                LEFT JOIN items p ON p.item_id = d.item_id
                                LEFT JOIN item_rate r on r.id = d.item_rate_id
                                LEFT JOIN size s ON s.id = r.size_id
                                LEFT JOIN category ca ON ca.id = p.category_id
                                WHERE m.invoice_no='$invoice_no'
                                GROUP BY d.order_id
                                ORDER BY m.order_id";
                        //echo $sql; die;
                        $stmt = $conn->prepare($sql);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($result as $row) {
                            extract($row);
                        }
                        //echo 55; die;

                        $to 	 = $customer_email;
                        $from 	 = 'admin@cakencookie.net';
                        $subject = "#$invoice_no Order Confirmation from Cakencookie";
                        $body 	 = '';

                        $headers = 'From: ' . $from . "\r\n" .
                                'Reply-To: ' . $from . "\r\n" .
                                'Content-type: text/html; charset=iso-8859-1' . "\r\n" .
                                'X-Mailer: PHP/' . phpversion();

                        $body .= "
                            <link rel='stylesheet' type='text/css' href='http://mbrotherssolution.com/CnC/plugin/bootstrap/bootstrap.css'>
                            <div id='order-div'>
                                <div class='title text-center'>
                                    <h3 class='text-coffee left'> <a href='index.php'><img src='http://mbrotherssolution.com/CnC/images/logo.png' alt=''></a></h3>
                                    <h4 class='text-coffee left'>Order No # <span id='ord_title_vw'>$invoice_no</span></h4>
                                </div>
                                <div class='done_registration '>							    
                                    <div class='doc_content'>
                                        <div class='col-md-12'>
                                            <h4>Order Details:</h4>				
                                            <div class='byline'>
                                                <span id='ord_date'>Ordered Time: $order_date</span><br/> 
                                                <span id='dlv_date'>Delivery Time $delivery_date</span> <br/> 
                                                <span id='dlv_date'>Payment Status : $paid_status</span> <br/> 
                                                <span id='dlv_date'>Payment Method : $payment_method</span>
                                            </div>	
    
    
                                            <h4>Customer Details:</h4> 								
                                            <address id='customer_detail_vw'>
                                            $customer_name
                                            <br/><b>Mobile:</b>$customer_contact_no
                                            <br/><b>Address:</b>$customer_address
                                            </address>
                                        </div>
                                        <div id='ord_detail_vw col-md-12'> 
                                            <table class='table table-bordered col-md-12' >
                                                <thead>
                                                    <tr>
                                                        <th align='center'>Product</th>
                                                        <th width='18%' align='center'>Size</th>
                                                        <th width='10%' align='center'>Quantity</th>
                                                        <th width='18%' style='text-align:right'>Rate</th>                           
                                                        <th width='18%'  style='text-align:right'>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>";
                                            $order_info_arr = explode(',', $order_info);
                                            $order_total = 0;
                                            foreach($order_info_arr as $key=>$item_details){
                                                $item_details_arr = explode('#', $item_details);
                                                $total = ($item_details_arr[7]*$item_details_arr[6]);
                                                $body .= "<tr><td>".$item_details_arr[2]."</td><td align='left'>".$item_details_arr[4]."</td><td align='center'>".$item_details_arr[7]."</td><td align='right'>".$item_details_arr[6]."</td><td align='right'>".number_format($total,2)."</td></tr>";
                                                $order_total += $total;
                                            }

                                            $total_order_bill = $order_total-$discount_amount;
                                            $total_paid 	  = $total_paid_amount;
                                            $body .= '<tr><td colspan="4" align="right" ><b>Total Product Bill</b></td><td align="right"><b>'.number_format($order_total ,2).'</b></td></tr>';
                                            $body .= '<tr><td colspan="4" align="right" ><b>Discount Amount</b></td><td align="right"><b>'.number_format($discount_amount,2).'</b></td></tr>';
                                            $body .= '<tr><td colspan="4" align="right" ><b>Total Order Bill</b></td><td align="right"><b>'.number_format($total_order_bill,2).'</b></td></tr>';
                                            $body .= '<tr><td colspan="4" align="right" ><b>Delivery Charge</b></td><td align="right"><b>'.number_format($delivery_charge,2).'</b></td></tr>';
                                            $body .= '<tr><td colspan="4" align="right" ><b>Total Paid</b></td><td align="right"><b>'.number_format($total_paid,2).'</b></td></tr>';
                                            $body .= '<tr><td colspan="4" align="right" ><b>Balance</b></td><td align="right"><b>'.number_format((($total_order_bill+$delivery_charge)-$total_paid),2).'</b></td></tr>';

                                    $body .= "										
                                                </tbody>
                                            </table>
                                            <p>Note: <span id='note_vw'>$remarks</span></p>
                                            <p>Print Time :". date('Y-m-d h:m:s')."</p>
                                            <br />
                                            <p style='font-weight:bold; text-align:center'>Thank you. Hope we will see you soon </p>
                                        </div> 
                                    </div>									
                                </div>							
                            </div>
                        ";
                        //echo $body;die;
                        mail($to, $subject, $body, $headers);
                    }
                }

                //-------------------------------
                echo $invoice_no;
            }
        }
        else echo "0";
    break;


    case "get_order_details_by_invoice":
        //echo 1; die;
        $sql = "SELECT m.order_id, m.customer_id, 
                c.full_name customer_name, d.item_id, c.contact_no customer_contact_no, c.address customer_address,  m.order_id,
                GROUP_CONCAT(ca.name,' >> ',ca.id,'#',ca.id,'#',p.name,' (',ca.name,' )','#',p.item_id,'#',s.name,'#',d.item_rate_id,'#',d.item_rate,'#',d.quantity) order_info,
                m.order_date, m.delivery_date, m.delivery_type, m.discount_amount, m.total_paid_amount,
                m.outlet_id, m.address, m.delivery_charge_id, m.tax_amount,
                m.remarks, m.order_status, m.payment_status, m.payment_method, 
                m.payment_reference_no, m.invoice_no, m.total_order_amt,
                case payment_status when payment_status=1 then 'Not Paid' else 'Paid' end paid_status, 
                case payment_method when payment_method=1 then 'bKash' when payment_method=2 then 'Rocket'  else 'Cash On Delivery'  end payment_method
                FROM order_master m
                LEFT JOIN order_details d ON d.order_id = m.order_id
                LEFT JOIN customer_infos c ON c.customer_id = m.customer_id
                LEFT JOIN items p ON p.item_id = d.item_id
                LEFT JOIN item_rate r on r.id = d.item_rate_id
                LEFT JOIN size s ON s.id = r.size_id
                LEFT JOIN category ca ON ca.id = p.category_id
                WHERE m.invoice_no= '$order_id'
                GROUP BY d.order_id
                ORDER BY m.order_id";
    	//echo $sql;die;
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $data['records'][] = $row;
        }
        echo json_encode($data);

    break;


    case "get_customer_details":
        //echo '1'; die;
        $customer_details = $dbClass->getResultList("SELECT c.customer_id, c.full_name, c.loyalty_points, c.contact_no, c.age, c.address,
                                                    c.`status`, c.photo, c.email, c.remarks,
                                                    (CASE c.`status` WHEN 1 THEN 'Active' WHEN  0 THEN 'Inactive' END) status_text
                                                    FROM customer_infos c
                                                    WHERE c.customer_id='$customer_id'");
        //echo $customer_details; die;
        foreach ($customer_details as $row){
            $data['records'][] = $row;
        }
        echo json_encode($data);

    break;

    case "insert_or_update":
            if(isset($customer_id) && $customer_id != ""){
                $is_active=0;
                if(isset($_POST['is_active'])){
                    $is_active=1;
                }

                $columns_value = array(
                    'full_name'=>$customer_name,
                    'email'=>$email,
                    'address'=>$address,
                    'age'=>$age,
                    'contact_no'=>$contact_no,
                    'email'=>$email,
                    'status'=>$is_active
                );
                if($age != "" && $age != "0000-00-00"){
                    $dob = date("Y-m-d", strtotime($age));
                    $columns_value['age'] = $dob;
                }

                if(isset($_POST['remarks']))
                    $columns_value['remarks'] = $remarks;

                if(isset($_FILES['customer_image_upload']) && $_FILES['customer_image_upload']['name']!= ""){
                    $desired_dir = "../../admin/images/customer";
                    chmod( "../../admin/images/customer", 0777);
                    $file_name = $_FILES['customer_image_upload']['name'];
                    $file_size =$_FILES['customer_image_upload']['size'];
                    $file_tmp =$_FILES['customer_image_upload']['tmp_name'];
                    $file_type=$_FILES['customer_image_upload']['type'];
                    if($file_size < 5297152){
                        if(file_exists("$desired_dir/".$file_name)==false){
                            if(move_uploaded_file($file_tmp,"$desired_dir/".$file_name))
                                $photo = "$file_name";
                        }
                        else{//rename the file if another one exist
                            $new_dir="$desired_dir/".time().$file_name;
                            if(rename($file_tmp,$new_dir))
                                $photo =time()."$file_name";
                        }
                        $photo  = "/images/customer/".$photo;
                    }
                    else {
                        echo "Image size is too large!";die;
                    }
                    $columns_value['photo'] = $photo;
                }

                if($password != "" && $new_password != ""){
                    $old_password =  $dbClass->getResultList("SELECT password FROM customer_infos WHERE customer_id=$customer_id");
                    if(md5($password) == $old_password[0]){
                        $columns_value['password'] = md5($new_password);
                    }
                    else{
                        echo "3";die;
                    }
                }

            //	var_dump($columns_value);
            //	die;
                $condition_array = array(
                    'customer_id'=>$customer_id
                );
                $return = $dbClass->update("customer_infos", $columns_value, $condition_array);

                if($return) echo "2";
                else 	echo "0";
            }
            else
                echo "0";

        break;


	case "get_settings_details":
        //echo '1'; die;
        $general_settings = $dbClass->getResultList("SELECT *
												FROM general_settings s
												WHERE s.id='1'");
        //echo $customer_details; die;
        foreach ($general_settings as $row){
            $data['records'][] = $row;
        }
        echo json_encode($data);
    break;





}






?>