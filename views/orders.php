<?php 
session_start();
include("../includes/dbConnect.php");
include("../includes/dbClass.php");
$dbClass = new dbClass;
$is_logged_in_customer = "";

if(!isset($_SESSION['customer_id']) && $_SESSION['customer_id']!=""){ ob_start(); header("Location:index.php"); exit();}
else $is_logged_in_customer = 1;
$customer_id = $_SESSION['customer_id'];	
$orders_info = $dbClass->getResultList("SELECT invoice_no order_no, order_id, order_date,delivery_date, 
										CASE delivery_type WHEN 1 THEN 'Takeout' WHEN 2 THEN 'Delevery' END delevery_type, 
										CASE order_status WHEN 1 THEN 'Ordered' WHEN 2 THEN 'Ready' WHEN 3 THEN 'Delevered' END order_status, 
										total_order_amt,total_paid_amount
										FROM order_master
										WHERE customer_id=$customer_id 
										order by order_id desc
										");
if(empty($orders_info)){
	echo "<h6 class='center'>Your have no orders </h6>";
}
else{
	
?>
	<h6 class="center">Your Order List </h6>
	<hr>
	<section class="home-icon shop-cart bg-skeen" style="padding-top: 20px">
		<div class="container" style="max-width:100%" id="oredrs_div">		
			<table class="table table-bordered table-hover" id="table_big" style="display: none">
				  <thead>
					<tr style="background-color: #e4b95b; alignment: center">
					  <th>Order No</th>
					  <th>Order Date</th>
					  <th>Delevery date</th>
					  <th>Type</th>
					  <th>Amount</th>
					  <th>Paid</th>
					  <th>Status</th>
					  <th></th>
					</tr>
				  </thead>
				  <tbody>
					<tr>
					 <?php
					 foreach($orders_info as $order){ 	
						$order_no = '"'.$order['order_no'].'"';						 
						echo 
						"<tr>
							  <td style='background-color: #e4b95b'>".$order['order_no']."</td>
							  <td>".$order['order_date']."</td>
							  <td>".$order['delivery_date']."</td>
							  <td>".$order['delevery_type']."</td>
							  <td>".$order['total_order_amt']."</td>
							  <td>".$order['total_paid_amount']."</td>
							  <td>".$order['order_status']."</td>
							  <td style='background-color: #e4b95b'><i class='fa fa-search-plus pointer' onclick='view_order(".$order_no.")'></i></td>
						  </tr>
						";
					  }
					  ?>
					</tr>
				  </tbody>
			</table>
            <table class="table table-bordered table-hover" id="" style="display: none; width: 100%">
                <thead>
                <tr style="background-color: #e4b95b; alignment: center">
                    <th style='font-size: 13px'>Order No</th>
                    <th style='font-size: 13px'>Amount</th>
                    <th style='font-size: 13px'>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <?php
                    foreach($orders_info as $order){
                        $order_no = '"'.$order['order_no'].'"';
                        echo
                            "<tr>
							  <td style='background-color: #e4b95b; font-size: 11px'>".$order['order_no']."</td>
							  <td style='font-size: 11px'>".$order['total_order_amt']."</td>
							  <td style='font-size: 11px'>".$order['order_status']."</td>
							  <td style='background-color: #e4b95b'><i class='fa fa-search-plus pointer' onclick='view_order(".$order_no.")'></i></td>
						  </tr>
						";
                    }
                    ?>
                </tr>
                </tbody>
            </table>

            <table class="table table-bordered table-hover" id="table_small" style="display: none">
                <thead>
                <tr style="background-color: #e4b95b; alignment: center">
                    <th style='font-size: 13px;width: 36%'>Order No</th>
                    <th style='font-size: 13px; width: 27%'>Amount</th>
                    <th style='font-size: 13px; width: 27%'>Status</th>
                    <th style="width: 10%"></th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <?php
                    foreach($orders_info as $order){
                        $order_no = '"'.$order['order_no'].'"';
                        echo
                            "<tr>
							  <td style='background-color: #e4b95b; font-size: 12px'>".$order['order_no']."</td>
							  <td style='font-size: 12px'>".$order['total_order_amt']."</td>
							  <td style='font-size: 12px'>".$order['order_status']."</td>
							  <td style='background-color: #e4b95b'><i class='fa fa-search-plus pointer' onclick='view_order(".$order_no.")'></i></td>
						  </tr>
						";
                    }
                    ?>
                </tr>
                </tbody>
            </table>
		</div>
	</section>
<?php
}
?>

<!-- Start Order details -->
<div class="modal fade booktable" id="order_modal" tabindex="-2" role="dialog" aria-labelledby="booktable">
    <div class="modal-dialog" role="document" style="width:80% !important">
        <div class="modal-content">
            <div class="modal-body">
                <div id="order-div">
                    <div class="title text-center">
                        <h3 class="text-coffee left"> <a href="index.php"><img src="/images/logo.png" alt=""></a></h3>
                        <h4 class="text-coffee left">Order No # <span id="ord_title_vw"></span></h4>
                    </div>
                    <div class="done_registration ">
                        <div class="doc_content">
                            <div class="col-md-12">
                                <div class="col-md-6">
                                    <h4>Order Details:</h4>
                                    <div class="byline">
                                        <span id="ord_date"></span><br/>
                                        <span id="dlv_date"></span> <br/>
                                        <span id="dlv_ps"></span> <br/>
                                        <span id="dlv_pm"></span>
                                    </div>
                                </div>
                                <div class="col-md-6" style="text-align:right">
                                    <h4>Customer Details:</h4>
                                    <address id="customer_detail_vw">
                                    </address>
                                </div>
                            </div>
                            <div id="ord_detail_vw">
                                <table class="table table-bordered" >
                                    <thead>
                                    <tr>
                                        <th align="center">Product</th>
                                        <th width="18%" align="center">Size</th>
                                        <th width="10%" align="center">Quantity</th>
                                        <th width="18%" style="text-align:right">Rate</th>
                                        <th width="18%"  style="text-align:right">Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                                <p>Note: <span id="note_vw"></span></p>
                                <p>Print Time : <?php echo date("Y-m-d h:m:s"); ?></p>
                                <br />
                                <p style="font-weight:bold; text-align:center">Thank you. Hope we will see you soon </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 center"> <button type="button" class="btn btn-warning" id="order_print"><i class="fa fa-lg fa-print"></i></button></div>
            </div>
        </div>
    </div>
</div>
<!-- End order -->

<script>
    var sWidth = window.screen.width;
    var print_module=''
    //alert("sWidth is: " + sWidth);
    if(sWidth<801){
        $('#table_big').css('display', 'none')
        $('#table_small').css('display', 'block')
    }
    else {
        $('#table_big').css('display', 'block')
        $('#table_small').css('display', 'none')
    }

    var view_order = function view_order(order_id){
        //alert('ok')
        $('#ord_detail_vw>table>tbody').html('');
        $.ajax({
            url:"./includes/controller/ecommerceController.php",
            type:'POST',
            async:false,
            dataType: "json",
            data:{
                q: "get_order_details_by_invoice",
                order_id:order_id
            },
            success: function(data){
                //alert(data.item_id)
                if(!jQuery.isEmptyObject(data.records)){
                    $.each(data.records, function(i,data){
                        $('#ord_title_vw').html(data.invoice_no);
                        $('#ord_date').html("Ordered time: "+data.order_date);
                        $('#dlv_date').html("Delivery time: "+data.delivery_date);
                        $('#dlv_ps').html("Payment Status: "+data.paid_status);
                        $('#dlv_pm').html("Payment Method: "+data.payment_method);
                        $('#customercustomer_detail_vw').html(" "+data.customer_name+"<br/><b>Mobile:</b> "+data.customer_contact_no+"<br/><b>Address:</b> "+data.customer_address);
                        $('#note_vw').html(data.remarks);

                        var order_tr = "";
                        var order_total = 0;
                        order_infos	 = data.order_info;
                        var order_arr = order_infos.split(',');
                        $.each(order_arr, function(i,orderInfo){
                            var order_info_arr = orderInfo.split('#');
                            var total = ((parseFloat(order_info_arr[6])*parseFloat(order_info_arr[7])));
                            order_tr += '<tr><td>'+order_info_arr[2]+'</td><td align="left">'+order_info_arr[4]+'</td><td align="center">'+order_info_arr[7]+'</td><td align="right">'+order_info_arr[6]+'</td><td align="right">'+total.toFixed(2)+'</td></tr>';
                            order_total += total;
                        });
                        var total_order_bill = ((parseFloat(order_total)+parseFloat(data.delivery_charge))-parseFloat(data.discount_amount));
                        var total_paid = data.total_paid_amount;
                        order_tr += '<tr><td colspan="4" align="right" ><b>Total Product Bill</b></td><td align="right"><b>'+order_total.toFixed(2)+'</b></td></tr>';
                        order_tr += '<tr><td colspan="4" align="right" ><b>Discount Amount</b></td><td align="right"><b>'+data.discount_amount+'</b></td></tr>';
                        order_tr += '<tr><td colspan="4" align="right" ><b>Delivery Charge</b></td><td align="right"><b>'+data.delivery_charge+'</b></td></tr>';
                        order_tr += '<tr><td colspan="4" align="right" ><b>Total Order Bill</b></td><td align="right"><b>'+total_order_bill.toFixed(2)+'</b></td></tr>';
                        order_tr += '<tr><td colspan="4" align="right" ><b>Total Paid</b></td><td align="right"><b>'+total_paid+'</b></td></tr>';
                        order_tr += '<tr><td colspan="4" align="right" ><b>Balance</b></td><td align="right"><b>'+(total_order_bill-total_paid).toFixed(2)+'</b></td></tr>';
                        $('#ord_detail_vw>table>tbody').append(order_tr);



                        //for small device

                    });
                }
            }
        });
        $('#order_modal').modal();
    }

</script>

