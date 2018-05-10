<?php


if(isset($_GET['place_id'])){
    $place=$_GET['place_id'];
    $place_request=file_get_contents("https://maps.googleapis.com/maps/api/place/details/json?placeid=".$place."&key=AIzaSyCkQkQOa6xaqAu3Is6yhQBla6Jj-icdj8A");
    $place_json=json_decode($place_request,true);

    $response=array("results"=>getReviews($place_json),"photos"=>getPhotos($place_json));
    $response_json=json_encode($response);
    echo $response_json;
    die();
}

function getReviews($element){

    $review_array=array();
    if(!array_key_exists('reviews',$element['result'])){
        return $review_array;
    }
    
    $review=$element['result']['reviews'];
    $num_of_reviews=count($review);
    if($num_of_reviews>5){
        $num_of_reviews=5;
    }
    
    for($i=0;$i<$num_of_reviews;$i++){
        array_push($review_array,array($review[$i]['profile_photo_url'],$review[$i]['author_name'],$review[$i]['text']));
    }

    return $review_array;
}

function getPhotos($element){
    $num_of_photos=0;
    if(!array_key_exists('photos',$element['result'])){
        return $num_of_photos;
    }
    $photos=$element['result']['photos'];
    $num_of_photos=count($photos);
    if($num_of_photos>5){
        $num_of_photos=5;
    }
    for($i=0;$i<$num_of_photos;$i++){
        $photo=file_get_contents("https://maps.googleapis.com/maps/api/place/photo?maxwidth=4096&photoreference=".$photos[$i]['photo_reference']."&key=AIzaSyClk73-b29Q043BLKzkIPpSDkSe6N8naKI");
        $photo_url="./".$i.".jpeg";
       
        $boolean=file_put_contents($photo_url,$photo);
        chmod($photo_url,0755);
    }

    return $num_of_photos;
}

?>

<?php
    if(isset($_GET['search'])){
    $keyword=$_GET['key_value'];
    $category=$_GET['category_value'];
    $radio=$_GET['radio_select'];
    $distance=$_GET['distance_value']*1.609*1000;
    $location=$_GET['location_value'];
    $latitude=$_GET['lat'];
    $longitude=$_GET['lon'];

    if($radio=="1"){
        $geoloc_request=file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($location)."&key="."AIzaSyD1Pwm8mjfqj7s1UEkVzP76hgERs1Kp3UM");
        $new_location=json_decode($geoloc_request,true);

        $new_lat=$new_location['results'][0]['geometry']['location']['lat'];
        $new_long=$new_location['results'][0]['geometry']['location']['lng'];

        $place_request=file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location="."$new_lat".","."$new_long"."&radius="."$distance"."&type=".urlencode($category)."&keyword=".urlencode($keyword)."&key="."AIzaSyDEPqDLabDaCnKEP7TLfXHEFUetaLexb4c");
        $place_array=json_decode($place_request,true);
        $place_array["latitude"]=$new_lat;
        $place_array["longitude"]=$new_long;
        echo json_encode($place_array);
        die();
    }
    else{
        $place_request=file_get_contents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?location="."$latitude".","."$longitude"."&radius="."$distance"."&type=".urlencode($category)."&keyword=".urlencode($keyword)."&key="."AIzaSyDEPqDLabDaCnKEP7TLfXHEFUetaLexb4c");
        //$place_array=json_decode($place_request,true);
        //$place_array["latitude"]=$latitude;
        //$place_array["longitude"]=$longitude;
        echo $place_request;
        die();
    }
}
?>





<html>
<style>
    #border-box{
        margin-left:250px;
        border:1px;
        border-color:grey;
        border-style: solid;
        height: 230px;
        width: 600px;
        padding-left: 150px;
    }

    #line{
        margin-left:-150px;
        margin-top:-20px;
        color: grey;
    
    }

    .button{
    background-color: Transparent;
    background-repeat:no-repeat;
    border: none;
    cursor:pointer;
    overflow: hidden;
    outline:none;
}

    #loc{
        margin-left:305px;
    }

    #map {
        height: 300px;
        width:300px;
        z-index:3;
    }
    #way{
        z-index:5;
        display:none;
    }

    a{
        text-decoration: none;
        color: black;
    }

</style>
<head>
    <title>Travel and Entertainment Search</title>

    <script>

    var lat,long;

    function getloc(element){
    lat=element.lat;
    long=element.lon;       
    document.getElementById("search").disabled=false;
    }



        function disable(){
            document.getElementById("loc1").disabled=true;
            
        }
        function enable(){
            document.getElementById("loc1").disabled=false;

        function disablesearch(){
            document.getElementById("search").disabled=true;
        }
        }
        
   
    var reviewboolean,photoboolean;
    var rev_open=0,photo_open=0;
    //var latitude,longitude;
    

    function getFormAttributes(){
    
        var keyword=document.getElementById("keyword_value").value;
        var category=document.getElementById("category_value").value;
        var distance=document.getElementById("distance_value").value;
        if(distance==""){
            distance="10";
        }
        var radio="";
        if(document.getElementById("here").checked)
        {
            radio="0";
        }
        else{
            radio="1";
        }
        var location="";
        if(radio=="1"){
            location=document.getElementById("loc1").value;
        }
        var search=document.getElementById("search").value;
        var parameters="key_value="+keyword+"&category_value="+category+"&distance_value="+distance+"&radio_select="+radio+"&location_value="+location+"&search="+search+"&lat="+lat+"&lon="+long;
        parameters=parameters.split(' ').join('+');
        return parameters;
    }  
    
    function decode_json(element){
    
        var number_of_places=element.results.length;
        data= "<table border=2 width=800px;><th>Category</th><th>Name</th><th>Address</th>";
        for(var i=0;i<number_of_places;i++){
            data+="<tr><td>"+'<img src="'+element.results[i].icon+ '" height="50" weight="50"></td>';
            data+="<td>"+'<a href="#" onclick=getReviewsAndPhotos("'+element.results[i].place_id+'","'+encodeURIComponent(element.results[i].name)+'")>'+element.results[i].name+'</a></td>';
            data+="<td>"+'<a href="#!" id="'+element.results[i].place_id+'" onclick=getMaps(this,'+element.results[i].geometry.location.lat+','+element.results[i].geometry.location.lng+')>'+element.results[i].vicinity+"</a></td></tr>";
        }
        data+="</table>";
        if(number_of_places==0){
            data="No results are found!";
        }
        document.getElementById("newelement").innerHTML=data;
    
    }
    
    function get_table(){
        var check = document.querySelector('#inputs').reportValidity();
        if(check==false){ return;}
        var attributes=getFormAttributes();
        var page_url="http://cs-server.usc.edu:36193/Homework6.php?"+attributes;
        var req=new XMLHttpRequest();
        req.onreadystatechange=function(){
            if (req.readyState == 4 && req.status == 200){
                //console.log(req.responseText);
                console.log(lat);
                console.log(long);
                json_object=JSON.parse(req.responseText);
                decode_json(json_object);
                if(!json_object.latitude=="")
                    {
                        lat=json_object.latitude;
                        long=json_object.longitude;
                    }
                console.log(lat);
                console.log(long);
            }
        }
        req.open("GET",page_url,true);
        req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        req.send();
    }
    function showreviews(){
        

        if(rev_open==1){
            //close the review section
            rev_open=0;
            document.getElementById("reviewarrow").src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
            document.getElementById("reviewinfo").innerHTML="click here to show reviews";
            document.getElementById("reviewspace").style.display="none";

        }
        
        else{
            if(photo_open==1){
                showphotos();
            }
            //open the review section
            rev_open=1;
            document.getElementById("reviewarrow").src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png";
            document.getElementById("reviewinfo").innerHTML="click here to hide reviews";
            document.getElementById("reviewspace").style.display="block";
        }
        
    }
    function showphotos(){

        if(photo_open==1){
            
            //close photo section
            photo_open=0;
            document.getElementById("photoarrow").src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png";
            document.getElementById("photoinfo").innerHTML="click here to show photos";
            document.getElementById("photospace").style.display="none";
        }
        else{
            if(rev_open==1){
                showreviews();
            }
            //open photo section
            photo_open=1;
            document.getElementById("photoarrow").src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_up.png";
            document.getElementById("photoinfo").innerHTML="click here to hide photos";
            document.getElementById("photospace").style.display="block";

        }
        

    }

    function showhtml(){
        var buttonhtml='<a onclick="showreviews()" ><div id ="reviewinfo" style="font-size:16;">click here to show reviews.</div><div><img id="reviewarrow" src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" height=25 width=35;></div> <div id="reviewspace" style="display:none;"></div>';
        buttonhtml+='<a onclick="showphotos()" ><div id ="photoinfo" style="font-size:16;">click here to show photos.</div><div><img id="photoarrow" src="http://cs-server.usc.edu:45678/hw/hw6/images/arrow_down.png" height=25 width=35;></div> <div id="photospace" style="display:none;"></div>';

        document.getElementById("newelement").innerHTML+=buttonhtml;
    }

    function getReviewsAndPhotos(id,name)
    {
        //set the innerhtml with photos and reviews links
        document.getElementById("newelement").innerHTML="<p>"+decodeURIComponent(name)+"</p>";
        showhtml();
        var p_id="place_id="+id;
        var pageurl="http://cs-server.usc.edu:36193/Homework6.php?"+p_id;
        var req =new XMLHttpRequest();
        req.onreadystatechange=function() {
        if (req.readyState == 4 && req.status == 200) {
              //document.getElementById("newelement").innerHTML=request.responseText;

            json_obj=JSON.parse(req.responseText);
            console.log(req.responseText);

            review_table="";
            var num_of_reviews=json_obj.results.length;
            
            review_table="<center><table width=700px border=2>"


            for(var i=0;i<num_of_reviews;i++)
            {
                review_table+='<tr><td><span><center><img height=40 width=40 src="'+json_obj.results[i][0]+'"><b>  '+json_obj.results[i][1]+'</b><center></span></td></tr>';
                review_table+='<tr><td>'+json_obj.results[i][2]+'</td></tr>';
            }
            review_table+="</table></center>";

            photos_table="";
            var num_of_photos=json_obj.photos;
            //  document.write('num_reviews');
            
            photos_table="<center><table width=700px border=2>"


            for(var i=0;i<num_of_photos;i++)
            {
            photos_table+='<tr><td><center><a target="_blank" href="http://cs-server.usc.edu:36193/'+i+'.jpeg">'+'<img height=500 width=700 src="http://cs-server.usc.edu:36193/'+i+'.jpeg"></a><center></td></tr>';
            }
            photos_table+="</table></center>";
            if(num_of_photos==0)
            {
                photos_table="No photos found.";
            }
            if(num_of_reviews==0)
            {
                review_table="No reviews found.";
            }
            //document.getElementById("newelement").innerHTML=review_table+"<br>"+photos_table;
            document.getElementById("photospace").innerHTML=photos_table;
            document.getElementById("reviewspace").innerHTML=review_table;
        }
        }
        req.open("GET",pageurl,true);
        req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        req.send();
    }

    

    
    function getMaps(link,new_lat,new_lon){
            //display maps and buttons on and off 
            if(document.getElementById("map").style.display=="block"){
                document.getElementById("map").style.display="none";
                document.getElementById("way").style.display="none";
                return;
            }

            var clickedlink=document.getElementById(link.id);
            var pos=clickedlink.getBoundingClientRect();
            var ver=pos.top+pageYOffset+20;
            var hor=pos.left+pageXOffset;
            document.getElementById("map").style="z-index:3;position:absolute;top:"+ver+"px;left:"+hor+"px;display:block;";
            document.getElementById("way").style="z-index:5;position:absolute;top:"+ver+"px;left:"+hor+"px;display:block;";
        var showDirections = new google.maps.DirectionsRenderer;
        var directionsService = new google.maps.DirectionsService;
        var map = new google.maps.Map(document.getElementById('map'), {
        zoom: 14,
        center: {lat: new_lat, lng: new_lon}});

        
        var marker = new google.maps.Marker({
          position: {lat:new_lat,lng:new_lon},
          map: map
        });
        showDirections.setMap(map);
    
        //showRoute(directionsService, showDirections,new_lat,new_lon);
        document.getElementById('1').addEventListener('click', function() {
        showRoute(marker,directionsService, showDirections,new_lat,new_lon,"WALKING");});
        document.getElementById('2').addEventListener('click', function() {
        showRoute(marker,directionsService, showDirections,new_lat,new_lon,"BICYCLING");});
        document.getElementById('3').addEventListener('click', function() {
        showRoute(marker,directionsService, showDirections,new_lat,new_lon,"DRIVING");});
    }
    
    function showRoute(marker,directionsService, showDirections,new_lat,new_lon,type){
        marker.setMap(null);
        var chosenPath = type;
        directionsService.route({
        origin: {lat: lat, lng: long},
        destination: {lat: new_lat, lng: new_lon},  
        
        travelMode: google.maps.TravelMode[chosenPath]}, function(response, status) {
        if (status == 'OK') {
          showDirections.setDirections(response);
        } else {
          window.alert('Directions request failed due to ' + status);
        }
        });
    }
    function clear_page(){
        document.getElementById("inputs").reset();
        document.getElementById("newelement").innerHTML="";
        document.getElementById("map").style.display="none";
        document.getElementById("way").style.display="none";
        return;

    }
    
    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDhfGkSkeHkRy2uKNTqTsz9YXNjRJa3iSA"></script>
  
</head>

<body>
    <div id="border-box" >
    <p  style="font-size:30px; color: black; font-style:italic;">Travel and Entertainment Search</p>
    <hr id="line">
    <div>
    <form id= "inputs">
        <b>Keyword</b><input id="keyword_value" type="text" name="keyword" required><br>
        <b>Category</b><select id="category_value" name="category">
            <option value="default">default</option>
            <option value="cafe">cafe</option>
            <option value="bakery">bakery</option>
            <option value="restaurant">restaurant</option>
            <option value="beauty_salon">beauty salon</option>
            <option value="casino">casino</option>
            <option value="movie_theatre">movie theatre</option>
            <option value="lodging">lodging</option>
            <option value="airport">airport</option>
            <option value="train_station">train station</option>
            <option value="subway_station">subway station</option>
            <option value="bus_station">bus station</option>
          </select><br>
        <b>Distance(miles)</b><input id="distance_value" type="text" name="distance" value="10";><b>from</b>
        <input id="here" type="radio" name="radio" value="here"   checked="checked" onclick="disable();"> Here<br>
        <input  id="loc" type="radio" name="radio" value="location" onclick="enable();"><input id="loc1"  type="text" placeholder="location" name="location" required disabled><br><br>
        <input id="search" type="button" name="search" value="Search" disabled="disabled" onclick="get_table()">
        <input type="reset" name="Clear" value="Clear" onclick="clear_page()">
</form>
    
    </div>
    </div>
<br>
<br>
<br>
<center>
<div id="newelement">


</div>
<div id="map"></div> 
<div id="way">
  <button id="1" value="WALKING">Walk There</button><br>
  <button id="2" value="BICYCLING">Bike there</button><br>
  <button id="3" value="DRIVING">Drive there</button>
 
</div>
</center>  

 

</body>

<script src="http://ip-api.com/json?callback=getloc"></script>


</html>
