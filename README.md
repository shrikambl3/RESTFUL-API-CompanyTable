# RESTFUL-API-CompanyTable

>Restful web service to host company information. Returns json data. <br>
POSTMAN link : https://documenter.getpostman.com/view/4373854/connxus/RW84rB2S
* **URLS**
  >BASEURL/ConnxusApi.php
* **Method:**
 > `GET`, `POST`, `DELETE`, `PUT`
* **GET**
>Accepted Params: id <br>
Curl: ```curl "BASEURL/ConnxusApi.php/ID"``` 

* **POST**
>Accepted Params: name, description, address, address2, city, state, zip <br>
Curl: ``` curl -d "name=example1&address=queen&city=cincinnati&state=Ohio&zip=45220&description=123&address2=condo" "BASEURL/ConnxusApi.php/ID"``` 
* **DELETE**
>Accepted Params: id <br>
Curl: ```curl -X "DELETE" "BASEURL/ConnxusApi.php/ID"``` 
* **PUT**
>Accepted Params: id, company (json string) <br>
Curl: ```curl -X PUT -H "Content-Type: application/json" -d '{"company":{"name":"XXX","address":["queen","condo"],"city":"cincinnati","state":"Ohio","zip":"45220"}}' "BASEURL/ConnxusApi.php/ID"```

