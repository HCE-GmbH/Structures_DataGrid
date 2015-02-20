<?php
$xml = <<<XML
<response>
  <date>today</date>
  <server>localhost</server>
  <records>
    <record>
      <firstname>Olivier</firstname>
      <lastname>Guilyardi</lastname>
      <city>Paris</city>
      <country>France</country>
    </record>
    <record>
      <firstname>Mark</firstname>
      <lastname>Wiesemann</lastname>
      <city>Aachen</city>
      <country>Germany</country>
    </record>
  </records>
</response>
XML;

// Options for the bind() call, using XPath
$options = array('path' => '/response/records/*');

// Bind the XML string
$test = $datagrid->bind($xml, $options, 'XML');

// Print binding error if any
if (MDB2::isError($test)) {
    echo $test->getMessage(); 
}
?>
