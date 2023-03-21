<?php
//Class to hold ways to communicate with CMI5 player through its API's -MB
class cmi5Connectors{

    public function getCreateTenant(){
        return [$this, 'createTenant'];
    }
    public function getRetrieveToken(){
        return [$this, 'retrieveToken'];
    }
    public function getRetrieveUrl(){
        return [$this, 'retrieveUrl'];
    }
    public function getCreateCourse(){
        return [$this, 'createCourse'];
    }

    public function getRetrieveAus(){
	return [$this, 'retrieveAus'];
 }
    public function retrieveAUs($returnedInfo, $record){
     
        //tab le .......HEYYYY There is mprethan one LMSID as wekk!

        $returnedInfo = json_decode($returnedInfo, true);
	
      
        //The results come back as nested array under more then statments. We only want statements, and we want them separated into unique statments
        $resultChunked = array_chunk($returnedInfo["metadata"]["aus"], 1);


        //TODO, any benefit in saving some info to DB here?
	   /*
       $tables = new cmi5Tables;
	        //bring in functions from class cmi5_table_connectors
    	    $populateTable = $tables->getPopulateTable();
        */

	    //These values wqonts changge based on au so do here
		//In FACT, if we are goin to make this where tables stored
		//Maybe store it then aus, but look at that later TODO

        /*
        echo"<br>";
        echo"ResultChunked: ";
        var_dump($resultChunked["id"]);
        echo"<br>";
        echo"<br>";
        echo"I'm trying tooo gget the course id here: ";
        var_dump($returnedInfo["id"]);
        echo"<br>";
*/
	    $record->courseid = $returnedInfo["id"];

		$length = count($resultChunked);
		$courseAus = array( );
		//$testObjectAu = array();
        //Why is iteration unreachable? It's reachable in the other test file
        for($i = 0; $i < $length; $i++){
      
            
			$au = $resultChunked[$i][0]['auIndex'];
           /*
            echo"<br>";
            echo"Ok, what is au here>? ";
            var_dump($au);
            echo"<br>";
			*/
            
            //ok, it is now separating the au's so now we want the au for asking for url
			
            //which i beleiove is best taken from end of lmsID
			//right?
			//THEY HAVEAN AU INDEX!!!! LETS TRY THAT!!!!
            //TODO, do we want to save this to table, why is this here?
			$record->auid = $au;

			//$populateTable($record, "cmi5_urls");
			$courseAus[] = $au;
			//$testObjectAu[] = $auObject;
			//echo"<br>";
			//echo"Hows the array cominG?";
			
        }
            //echo"<br>";
			//echo"What is course ID here ";
            
            //IT's an array of ints
            
            //var_dump($courseAus);
			//echo"<br>";
		
			

	   	return $courseAus;
        //Whaty is returned info here
        //var_dump($returnedInfo);
        //Or wait!! The entire course info is saved right? So maybe
        //just  retreive them when needed/ getting the url
        //Maybe here stores aUs and or in create course? 
        //Then alter they can be pulled out for launch url
       // $lmsId = $returnedInfo["lmsId"] . "/au/0";
       // $record->courseid = $returnedInfo["id"];
       // $record->cmi5activityid = $lmsId;
        //create url for sending to when requesting launch url for course 
        //$url = $record->cmi5playerurl . "/api/v1/". $record->courseid. "/launch-url/0";
        //$record->launchurl = $url;
    }

    //Function to create a course
    // @param $id - tenant id in Moodle
    // @param $token - tenant bearer token
    // @param $fileName - The filename of the course to be imported, to be added to url POST request 
    // @return  $result - Response from cmi5 player
    public function createCourse($id, $tenantToken, $fileName){

        global $DB, $CFG;
        $settings = cmi5launch_settings($id);

        //retrieve and assign params
        $token = $tenantToken;
        $file = $fileName;

        //Build URL to import course to
        $url= $settings['cmi5launchplayerurl'] . "/api/v1/course" ;
       
        //the body of the request must be made as array first
        $data = $file;
  
        //sends the stream to the specified URL 
        $result = $this->sendRequest($data, $url, $token);

        if ($result === FALSE) {

            if ($CFG->debugdeveloper) {
                echo "Something went wrong sending the request";
                echo "<br>";
                echo "Dumping session to troubleshoot.";
                var_dump($_SESSION);
                echo "<br>";
            }
	     } else {

			//Return an array with tenant name and info
			return $result;
		}
    }

    //////
    //Function to create a tenant
    // @param $urlToSend - URL retrieved from user in URL textbox
    // @param $user - username 
    // @param $pass - password 
    // @param $newTenantName - the name the new tenant will be, retreived from Tenant Name textbox
    /////
    public function createTenant($urlToSend, $user, $pass, $newTenantName){

        global $CFG;
        //retrieve and assign params
        $url = $urlToSend;
        $username = $user;
        $password = $pass;
        $tenant = $newTenantName;
    
        //the body of the request must be made as array first
        $data = array(
            'code' => $tenant);
    
        //sends the stream to the specified URL 
        $result = $this->sendRequest($data, $url, $username, $password);

        if ($result === FALSE){
            if ($CFG->debugdeveloper)  {
                    echo "Something went wrong!";
                    echo "<br>";
                    var_dump($_SESSION);
                }
        }
        
        //decode returned response into array
        $returnedInfo = json_decode($result, true);
            
        //Return an array with tenant name and info
        return $returnedInfo;
    }
    
    
     //@param $urlToSend - URL to send request to
    // @param $user - username
    // @param $pass - password
    // @param $audience - the name the of the audience using the token,
    // @param #tenantId - the id of the tenant
     function retrieveToken($urlToSend, $user, $pass, $audience, $tenantId){

        global $CFG;
        //retrieve and assign params
        $url = $urlToSend;
        $username = $user;
        $password = $pass;
        $tokenUser = $audience;
        $id = $tenantId;
    
        //the body of the request must be made as array first
        $data = array(
            'tenantId' => $id,
            'audience' => $tokenUser
        );
    
        //sends the stream to the specified URL 
        $token = $this->sendRequest($data, $url, $username, $password);

        if ($token === FALSE){

            if ($CFG->debugdeveloper)  {
                echo "Something went wrong!";
                echo "<br>";
                var_dump($_SESSION);
                }
        }
        else{
            return $token;
        }

}

    ///Function to retrieve a launch URL for course
    //@param $id -Actor id to find correct info for url request
    //@param $retUrl - returnUrl to pass to cmi5 in request
    //@param $id -Actor id to find correct info for url request
    //@return $url - The launch URL returned from cmi5 player
    ////////
    //Trying somehting new, maybe just pass in id instead of above params?
    public function retrieveUrl($id, $retUrl){
		global $DB;

		//Retrieve actor record, this enables correct actor info for URL storage
		$record = $DB->get_record("cmi5launch", array('id' => $id));

		$settings = cmi5launch_settings($id);

        $homepage = $settings['cmi5launchcustomacchp'];
        $returnUrl= $retUrl;
		$actor= $settings['cmi5launchtenantname'];
		$token = $settings['cmi5launchtenanttoken'];
		$playerUrl = $settings['cmi5launchplayerurl'];
		$courseId = $record->courseid;

        //MB
        //see here right here! IT is sending 0 as in thats the plain thing?
        //We could parse the aus and use them ehre? then iterate throught them
        //so like func that parses is called,
        //it returnes array?
        //arrray iterates through them, saving to table,
        //below here retreives ALL the urls
        //But then like, which one does it know to return?
        //That's where the tree comes in right?
        //Or maybe it's not done here at all, it's done elsewhere and here 
        //is where it picks what au it wants and sends off for launch url?
	   	//MB
		//needs course REsults/records 
		//Should the retrieve aus go here?
		//This is the retreive URL, so it can get the urls
		//as well...
		$courseResults = $record->courseinfo;
	
		//This currently retreives ana rray of the AU numbers,
		//TODO - do we want each number to also have the entire 'au info'? Or just numbers?
		//This needs to be a different jkind of retrieval. The retrieve URL NOW is actually creating them, this needs to ull
        //them from tables
        //Wait, it can have the same name riht? Cause its a local func
        //Although should this go in tables or aus??

        $courseAus = $this->retrieveAUs($courseResults, $record);
		//Now that we have the array get length and start getting separate URL!!!
		$length = count($courseAus);
		//Ok, now its an ARRAY of objects, to get au 'number' we want object->au
		
		for($i = 0; $i < $length; $i++){

            //Here it is trying to read an au property from my array of ints
			$au = $courseAus[$i];
		


	    $url = $playerUrl . "/api/v1/course/" . $courseId  ."/launch-url/" . $au;

        //the body of the request must be made as array first
        $data = array(
            'actor' => array (
                'account' => array(
                    "homePage" => $homepage,
                    "name" => $actor,
                )
            ),
            'returnUrl' => $returnUrl
        );
    
        // use key 'http' even if you send the request to https://...
        //There can be multiple headers but as an array under the ONE header
        //content(body) must be JSON encoded here, as that is what CMI5 player accepts
        //JSON_UNESCAPED_SLASHES used so http addresses are displayed correctly
     	   $options = array(
            'http' => array(
                'method'  => 'POST',
                'ignore_errors' => true,
                'header' => array("Authorization: Bearer ". $token,  
                    "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"),
                'content' => json_encode($data, JSON_UNESCAPED_SLASHES)

            )	
        );

        //the options are here placed into a stream to be sent
        $context  = stream_context_create(($options));

        //sends the stream to the specified URL and stores results (the false is use_include_path, which we dont want in this case, we want to go to the url)
        $launchResponse = file_get_contents( $url, false, $context );

	   //to bring in functions from class cmi5Connector
		$connectors = new cmi5Tables;
		$saveUrl = $connectors->getSaveURL();

	

        //Save the returned info to the correct table
		$saveUrl($id, $launchResponse, $returnUrl, $homepage);
		
		//Only return the URL
		$urlDecoded = json_decode($launchResponse, true);
		$url = $urlDecoded['url'];
	};//end of my trial for length new url thingy
        //return response
        return $url;
    }


        ///Function to construct, send an URL, and save result
        //@param $dataBody - the data that will be used to construct the body of request as JSON 
        //@param $url - The URL the request will be sent to
        //@param ...$tenantInfo is a variable length param. If one is passed, it is $token, if two it is $username and $password
        ///@return - $result is the response from cmi5 player
        /////
        public function sendRequest($dataBody, $urlDest, ...$tenantInfo) {
            $data = $dataBody;
            $url = $urlDest;
            $tenantInformation = $tenantInfo;
    
                //If number of args is greater than one it is for retrieving tenant info and args are username and password
                if(count($tenantInformation) > 1 ){
                
                    $username = $tenantInformation[0];
                    $password = $tenantInformation[1];

                    // use key 'http' even if you send the request to https://...
                    //There can be multiple headers but as an array under the ONE header
                    //content(body) must be JSON encoded here, as that is what CMI5 player accepts
                    $options = array(
                        'http' => array(
                            'method'  => 'POST',
                            'header' => array('Authorization: Basic '. base64_encode("$username:$password"),  
                                "Content-Type: application/json\r\n" .
                                "Accept: application/json\r\n"),
                            'content' => json_encode($data)
                        )
                    );
                    //the options are here placed into a stream to be sent
                    $context  = stream_context_create($options);
                
                    //sends the stream to the specified URL and stores results (the false is use_include_path, which we dont want in this case, we want to go to the url)
                    $result = file_get_contents( $url, false, $context );
                
                    //return response
                    return $result;
                }
            //Else the args are what we need for posting a course
          	  else{

				//First arg will be token
                	$token = $tenantInformation[0];
	            	$file_contents = $data->get_content();

                // use key 'http' even if you send the request to https://...
                //There can be multiple headers but as an array under the ONE header
                //content(body) must be JSON encoded here, as that is what CMI5 player accepts
                //JSON_UNESCAPED_SLASHES used so http addresses are displayed correctly
                $options = array(
                    'http' => array(
                        'method'  => 'POST',
                        'ignore_errors' => true,
                        'header' => array("Authorization: Bearer ". $token,  
                            "Content-Type: application/zip\r\n"), 
                        'content' => $file_contents
                    )
                );

                 //the options are here placed into a stream to be sent
                 $context  = stream_context_create(($options));
    
                 //sends the stream to the specified URL and stores results (the false is use_include_path, which we dont want in this case, we want to go to the url)
                 $result = file_get_contents( $url, false, $context );

      	      return $result;
                }
    }
}

    ?>