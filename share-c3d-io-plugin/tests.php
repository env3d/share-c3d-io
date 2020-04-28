<?

use PHPUnit\Framework\TestCase;
require_once('/var/www/html/includes/load-yourls.php' );

final class ShareC3DIOTest extends TestCase
{
    private $expiredToken = "eyJraWQiOiJSN2JtQURvcDlXaHloQVM4S0txQVlmRVwvelltN0VMTzFmQkNDUVpJMWUrdz0iLCJhbGciOiJSUzI1NiJ9.eyJhdF9oYXNoIjoiaG9PYWswTjYzOERCNHdteXJkS1BtQSIsInN1YiI6IjI5YWQ5NWM3LTViYjctNDcxOC05NmQ0LTVkM2M0NDRiYTk2NCIsImNvZ25pdG86Z3JvdXBzIjpbInVzLXdlc3QtMl9NWmZhcERoekhfR29vZ2xlIl0sImlzcyI6Imh0dHBzOlwvXC9jb2duaXRvLWlkcC51cy13ZXN0LTIuYW1hem9uYXdzLmNvbVwvdXMtd2VzdC0yX01aZmFwRGh6SCIsImNvZ25pdG86dXNlcm5hbWUiOiJHb29nbGVfMTEwNjkxNjIxNTk1MjI2OTMyNDEwIiwiZ2l2ZW5fbmFtZSI6Ikphc29uIiwiYXVkIjoiNDdpbGU4ZW1vN204ZmxuaGpmdWM1YWE5aTAiLCJpZGVudGl0aWVzIjpbeyJ1c2VySWQiOiIxMTA2OTE2MjE1OTUyMjY5MzI0MTAiLCJwcm92aWRlck5hbWUiOiJHb29nbGUiLCJwcm92aWRlclR5cGUiOiJHb29nbGUiLCJpc3N1ZXIiOm51bGwsInByaW1hcnkiOiJ0cnVlIiwiZGF0ZUNyZWF0ZWQiOiIxNTg3NTE5Njk0NDMxIn1dLCJ0b2tlbl91c2UiOiJpZCIsImF1dGhfdGltZSI6MTU4Nzg2MjczMSwibmFtZSI6Ikphc29uIE1hZGFyIiwiZXhwIjoxNTg3ODY2MzMxLCJpYXQiOjE1ODc4NjI3MzEsImZhbWlseV9uYW1lIjoiTWFkYXIiLCJlbWFpbCI6ImptYWRhckBnbWFpbC5jb20ifQ.xdrC_LlWAXWHY6p9y0s49CnMXNuBZ4V2FlvQX7mUDemmwBuexcAuUB9x4zE0-Lzf_RPD6btaMM0z4LvXoh00Z9PcOz_4-dw4i0r10ABEDHpxTUzB5lK0wNPEVtHyTeI1_N6NsfbdojUVg1vfxXo7zEzE3iGs7JbP9zo2IJI1Dv5cyEIofeFoz1XIGjqTit-BLnI2meVrDnv-IwiwzYNspAqqXSUNwpi1pSxzAmil8-B8RVrgqQBgxHbEc03iAogtd7y3IgpW71y4-WThDXH34zXEFWn2YLcjFU0CvoZJiT_CXNd4ZAm3a2X6voMKORJSgHYBJFW63twLLLaFkJpiRA";
    
    private function sendHttp( $header, $url )
    {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=> $header,
                'follow_location'=>false,
                'ignore_errors'=>true
            )
        );

        $context = stream_context_create($opts);
        $file = file_get_contents($url, false, $context);
        print_r($http_response_header);
        print($file);
        
        $result = json_decode($file);
        //print(json_encode($result, JSON_PRETTY_PRINT));

        return $result;
    }
    
    public function testDummy(): void
    {
        $this->assertTrue(true);
    }

    public function testOwnerEndpoint(): void
    {
        $result = $this->sendHttp('', 'http://localhost/owner');
        $this->assertTrue($result->{'errorCode'} === 400);
        
        $result = $this->sendHttp('', 'http://localhost/owner/');
        $this->assertTrue($result->{'errorCode'} === 400);
        
        $result = $this->sendHttp('', 'http://localhost/owner/jmadar');
        $this->assertTrue(isset($result->{'result'}));
    }

    public function testInvalidJWT(): void
    {
        $result = $this->sendHttp(
            "Authorization: Bearer THIS_IS_AN_INVALID_JWT\r\n",
            "http://localhost/shorturl/http://test.com"
        );                
        $this->assertTrue(substr($result->{'message'}, 0, strlen('JWT ERROR')) === 'JWT ERROR');
    }

    public function testExpiredJWT(): void
    {
        $result = $this->sendHttp(
            "Authorization: Bearer $this->expiredToken\r\n",
            "http://localhost/shorturl?url=http://test.com"
        );                
        $this->assertTrue(substr($result->{'message'}, 0, strlen('JWT ERROR')) === 'JWT ERROR');
    }

    public function testValidUser(): void
    {
        $result = $this->sendHttp('', 'http://localhost/shorturl?username=admin&password=admin&url=http://google.ca');
        $this->assertTrue($result->statusCode === 200);
        if ($result->url->keyword) yourls_delete_link_by_keyword( $result->url->keyword );
    }
    
    public function testValidJWT(): void
    {
        $idToken = $_ENV['ID_TOKEN'];
        $result = $this->sendHttp(
            "Authorization: Bearer $idToken",
            "http://localhost/shorturl?url=http://google.ca"
        );
        $this->assertTrue($result->statusCode === 200);
        if ($result->url->keyword) yourls_delete_link_by_keyword( $result->url->keyword );        
    }
    
}
?>
