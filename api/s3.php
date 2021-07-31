<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');

use Aws\S3\S3Client;


$client = S3Client::factory(array(
                    'version' => AWS_VERSION,
                    'key'    => AWS_KEY,
                    'secret' => AWS_SECRET,
                    'region' => AWS_REGION
    ));


//$client = $aws->get('data.ibeyonde');
//
$bucket='data.ibeyonde';

$result = $client->listBuckets();

foreach ($result['Buckets'] as $bucket) {
    echo "{$bucket['Name']} - {$bucket['CreationDate']}</br>";
}


$iterator = $client->getIterator('ListObjects', array(
        'Bucket' => 'data.ibeyonde', 'Deimiter' => '/', 'Prefix' => '036d2e1237bb4b8790c51961b06b4889/2016/06'
    ));

foreach ($iterator as $object) {
        echo $object['Key'] . "\n";
}

$furl = $client->getObjectUrl('data.ibeyonde', 'test.jpg', '+30 minutes');


echo "<img src=\"$furl\">test</img><br/>";

?>
