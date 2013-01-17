<?php
/**
 *
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package
 * @subpackage
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__).'/../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/repository/elis_files/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elis_files/lib/lib.php');


class filenodeXMLTest extends elis_database_test {

    protected static function get_overlay_tables() {
        return array(
        );
    }

    public function setupNode() {
        $node = new stdClass;
        $node->uuid = "urn:uuid:e54d46e5-f047-4c6c-a39e-258d1370671c";
        $node->id = "workspace://SpacesStore/e54d46e5-f047-4c6c-a39e-258d1370671c)";
        $node->properties = array(
            'cmis:name' => "Company Home",
            'cmis:baseTypeId' => "cmis:folder",
            'cmis:creationDate' => "2011-12-16T14:51:06.933-05:00",
            'cmis:lastModificationDate' => "2012-05-15T10:29:48.205-04:00",
            'cmis:objectId' => "workspace://SpacesStore/fab64d29-99a2-4ab2-b9ef-f231bf7e2559"
        );
        $node->links = array(
            'self' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c",
            'edit' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c",
            'http://docs.oasis-open.org/ns/cmis/link/200908/allowableactions' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/allowableactions",
            'http://docs.oasis-open.org/ns/cmis/link/200908/relationships' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/rels",
            'http://docs.oasis-open.org/ns/cmis/link/200908/policies' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/pols",
            'http://docs.oasis-open.org/ns/cmis/link/200908/acl' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/acl",
            'down' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/children",
            'down-tree' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/descendants",
            'http://docs.oasis-open.org/ns/cmis/link/200908/foldertree' => "http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/tree",
            'describedby' => "http://localhost:8080/alfresco/s/cmis/type/cmis:folder",
            'service' => "http://localhost:8080/alfresco/s/cmis"
        );
        return $node;
    }

    public function setupExpectedContent($node) {

        $expectedContentNode = new stdClass;
        $expectedContentNode->uuid    = str_replace('urn:uuid:', '', $node->uuid);
        $expectedContentNode->summary = ''; // Not returned with CMIS data
        $expectedContentNode->title   = $node->properties['cmis:name'];
        $expectedContentNode->icon    = '';
        $expectedContentNode->type    = $node->properties['cmis:baseTypeId'];

        return $expectedContentNode;
    }

    /**
     * Test the elis_files_process_node_new function that uuid, summary, title, icon and type are set.
     */
    public function testProcessNodeNewValidateBaseFields() {

        $node = $this->setupNode();

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);

        // assert that the base fields were set
        $this->assertEquals($expectedContentNode->uuid,$contentNode->uuid);
        $this->assertEquals($expectedContentNode->summary,$contentNode->summary);
        $this->assertEquals($expectedContentNode->title,$contentNode->title);
        $this->assertEquals($expectedContentNode->icon,$contentNode->icon);
        $this->assertEquals($expectedContentNode->type,$contentNode->type);
    }

    public function ownerProvider() {
        return array(
            array('cmis:lastModifiedBy','System'),
            array('cmis:createdBy','Moodleadmin'),
            array(null,'none')
        );
    }

    /**
     * Test the elis_files_process_node_new function that all possible values of owner are handled.
     * @dataProvider ownerProvider
     */
    public function testProcessNodeNewOwner($field,$data) {

        $node = $this->setupNode();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);
        $expectedContentNode->owner = $data;

        $this->assertEquals($expectedContentNode->owner,$contentNode->owner);

    }

    public function fileFieldsProvider() {
        return array(
            array('cmis:contentStreamFileName','filename','test.png'),
            array('cmis:contentStreamLength','filesize','3434'),
            array('cmis:contentStreamMimeType','filemimetype','image/png')
        );
    }

    /**
     * Test the elis_files_process_node_new function that file fields are processed.
     * @dataProvider fileFieldsProvider
     */
    public function testProcessNodeNewFileFields($field1,$field2,$data) {

        $node = $this->setupNode();
        // test the 3 different owners
        $node->properties[$field1] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);
        $expectedContentNode->$field2 = $data;

        $this->assertEquals($expectedContentNode->$field2,$contentNode->$field2);

    }

    public function createdProvider() {
        return array(
            array('cmis:creationDate','2012-01-20T13:46:00.038-05:00'),
            array('cmis:creationDate','2012-01-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the created time is processed.
     * @dataProvider createdProvider
     */
    public function testProcessNodeNewCreated($field,$data) {

        $node = $this->setupNode();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);
        // recreate created time
        $expectedContentNode->created = strtotime($data);

        $this->assertEquals($expectedContentNode->created,$contentNode->created);

    }

    public function updatedProvider() {
        return array(
            array('cmis:lastModificationDate','2012-02-20T13:46:00.038-05:00'),
            array('cmis:lastModificationDate','2012-02-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the updated time is processed.
     * @dataProvider updatedProvider
     */
    public function testProcessNodeNewUpdated($field,$data) {

        $node = $this->setupNode();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);
        // recreate updated time
        $expectedContentNode->modified = strtotime($data);

        $this->assertEquals($expectedContentNode->modified,$contentNode->modified);

    }

    public function linkFieldsProvider() {
        return array(
            array('down','children','http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/children'),
            array('down-tree','descendants','http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/descendants'),
            array('describedby','type','http://localhost:8080/alfresco/s/cmis/type/cmis:folder'),
            array('edit-media','fileurl','http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/3747b3f4-2e4c-4e8d-8e67-e2348fd65486/content.png')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the link fields are processed.
     * @dataProvider linkFieldsProvider
     */
    public function testProcessNodeNewLinkFields($field1,$field2,$data) {
        $node = $this->setupNode();
        // test the 4 link fields
        $node->links[$field1] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);
        // recreate the 4 link fields
        if ($field1 != 'edit-media') {
            $expectedContentNode->links[$field2] = $data;
            $this->assertEquals($expectedContentNode->links[$field2],$contentNode->links[$field2]);
        } else {
            $expectedContentNode->$field2 = $data;
            $this->assertEquals($expectedContentNode->$field2,$contentNode->$field2);
        }
    }

    public function nodeNewPropertiesProvider() {
        return array(
            array('cmis:objectId','workspace://SpacesStore/fab64d29-99a2-4ab2-b9ef-f231bf7e2559')
        );
    }

    /**
     * Test the elis_files_process_node_new function with objectid property.
     * @dataProvider nodeNewPropertiesProvider
     */
    public function testProcessNodeNewNoderef($field,$data) {

        $node = $this->setupNode();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentNode = elis_files_process_node_new($node, $type);

        $expectedContentNode = $this->setupExpectedContent($node);

        $this->assertEquals($data,$contentNode->noderef);

    }

    public function loadfolderDOMfromXML() {
        global $CFG;

        $dom = new DOMDocument();
        // load into dom
        $dom->load($CFG->dirroot.'/repository/elis_files/phpunit/folderresponse.xml');
        if (!$dom) {
            echo '********************** Error while parsing the folder';
            exit;
        }
        return $dom;
    }

    public function loadfileDOMfromXML() {
        global $CFG;

        $dom = new DOMDocument();
        // load into dom
        $dom->load($CFG->dirroot.'/repository/elis_files/phpunit/fileresponse.xml');
        if (!$dom) {
            echo '********************** Error while parsing the file';
            exit;
        }
        return $dom;
    }
    /**
     * Test the elis_files_process_node function with an empty node.
     */
    public function testProcessNodeNoChildNodes() {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $string = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">'.
        '<author><name>admin</name></author>'.
        '<generator version="3.2.1 (.2 3)">Alfresco (Enterprise)</generator>'.
        '<icon>http://localhost:8080/alfresco/images/logo/AlfrescoLogo16.ico</icon>'.
        '<id>urn:uuid:fff6c376-bf63-4475-a25d-383251dcb3fb-parent</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb/parent?alf_ticket=TICKET_edd0417f8ba654b6c4ac3f7c4317fbae31b648e9"></link>'.
        '<link rel="source" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb"></link>'.
        '<title>Import_questions.txt Parent</title>'.
        '<updated>2012-03-02T15:52:24.790-05:00</updated>'.
        '<entry>'.
        '</entry>'.
        '<cmis:hasMoreItems>false</cmis:hasMoreItems>'.
        '</feed>';
        $dom->loadXML($string);
        libxml_clear_errors();

        $nodes = $dom->getElementsByTagName('entry');

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);

        //leave as is and fix code - initialise contentnode to false or something
        $this->assertFalse($contentNode);
    }

    /**
     * Test the elis_files_process_node function with base fields id and author.
     */
    public function testProcessNodeOwnerAndUuid() {

        $dom = $this->loadfolderDOMfromXML();
        $xpath = new DOMXpath($dom);

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $id = $node->getElementsByTagName("id");
            $id = str_replace('urn:uuid:', '', $id->item(0)->nodeValue);
            $author = $node->getElementsByTagName("author")->item(0)->nodeValue;
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals($id,$contentNode->uuid);
        $this->assertEquals($author,$contentNode->owner);

    }

    public function nodeCreatedProvider() {
        return array(
            array('published','2012-01-20T13:46:00.038-05:00'),
            array('published','2012-01-20T13:30:00.038-05:30')
        );
    }
    /**
     * Test the elis_files_process_node function with the creation date.
     * @dataProvider nodeCreatedProvider
     */
    public function testProcessNodeCreated($field,$data) {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $published = $node->getElementsByTagName($field);
            $published->item(0)->nodeValue = $data;
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals(strtotime($data),$contentNode->created);

    }

    public function nodeUpdatedProvider() {
        return array(
            array('updated','2012-01-20T13:46:00.038-05:00'),
            array('updated','2012-01-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node function with the creation date.
     * @dataProvider nodeUpdatedProvider
     */
    public function testProcessNodeUpdated($field,$data) {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $updated = $node->getElementsByTagName($field);
            $updated->item(0)->nodeValue = $data;
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals(strtotime($data),$contentNode->modified);

    }

    /**
     * Test the elis_files_process_node function with the title, summary and icon.
     */
    public function testProcessNodeTitleSummaryIcon() {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $title = $node->getElementsByTagName("title")->item(0)->nodeValue;
            $summary = $node->getElementsByTagName("summary")->item(0)->nodeValue;
            //with a colon in the tag name, just use the second part of the name
            $icon = $node->getElementsByTagName("icon")->item(0)->nodeValue;
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals($title,$contentNode->title);
        $this->assertEquals($summary,$contentNode->summary);
        $this->assertEquals($icon,$contentNode->icon);

    }

    public function nodeLinkFieldsProvider() {
        return array(
            array('self','self'),
            array('allowableactions','permissions'),
            array('relationships','associations'),
            array('parents','parent'),
            array('children','children'),
            array('descendants','descendants'),
            array('type','type')
        );
    }

    /**
     * Test the elis_files_process_node function with links.
     * @dataProvider nodeLinkFieldsProvider
     */
    public function testProcessNodeLinks($field1,$field2) {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $value = '';
            $links = $node->getElementsByTagName("link");
            foreach ($links as $link) {
                if($link->getAttribute('rel') === $field1) {
                    $value = str_replace(elis_files_base_url(), '',$link->getAttribute('href'));
                    break;
                }
            }
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value,$contentNode->links[$field2]);

    }

    /**
     * Test the elis_files_process_node function with no cmis properties.
     */
    public function testProcessNodeNoCmisProperties() {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $string = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">'.
        '<author><name>admin</name></author>'.
        '<generator version="3.2.1 (.2 3)">Alfresco (Enterprise)</generator>'.
        '<icon>http://localhost:8080/alfresco/images/logo/AlfrescoLogo16.ico</icon>'.
        '<id>urn:uuid:fff6c376-bf63-4475-a25d-383251dcb3fb-parent</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb/parent?alf_ticket=TICKET_edd0417f8ba654b6c4ac3f7c4317fbae31b648e9"></link>'.
        '<link rel="source" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb"></link>'.
        '<title>Import_questions.txt Parent</title>'.
        '<updated>2012-03-02T15:52:24.790-05:00</updated>'.
        '<entry>'.
        '<author><name>System</name></author>'.
        '<content>d60de32a-9e4d-40c5-888a-08607fedf9f5</content>'.
        '<id>urn:uuid:d60de32a-9e4d-40c5-888a-08607fedf9f5</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="edit" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="allowableactions" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/permissions"></link>'.
        '<link rel="relationships" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/rels"></link>'.
        '<link rel="parents" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/parent"></link>'.
        '<link rel="children" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/children"></link>'.
        '<link rel="descendants" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/descendants"></link>'.
        '<link rel="type" href="http://localhost:8080/alfresco/s/api/type/folder"></link>'.
        '<link rel="repository" href="http://localhost:8080/alfresco/s/api/repository"></link>'.
        '<published>2012-02-17T10:49:07.215-05:00</published>'.
        '<summary>User Homes</summary><title>User Homes</title>'.
        '<updated>2012-02-17T10:49:07.230-05:00</updated>'.
        '<cmis:object>'.
            '<cmis:properties>'.
            '</cmis:properties>'.
        '</cmis:object>'.
        '<cmis:terminator></cmis:terminator>'.
        '</entry>'.
        '<cmis:hasMoreItems>false</cmis:hasMoreItems>'.
        '</feed>';
        $dom->loadXML($string);
        libxml_clear_errors();
        $nodes = $dom->getElementsByTagName('entry');

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentNode);
    }

    /**
     * Test the elis_files_process_node function with basetype folder.
     */
    public function testProcessNodeFolderBaseType() {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        $type = '';

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals(ELIS_files::$type_folder,$type);

    }

    /**
     * Test the elis_files_process_node function with basetype document.
     */
    public function testProcessNodeDocumentBaseType() {

        $dom = $this->loadfileDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');

        $type = '';

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals(ELIS_files::$type_document,$type);

    }

    public function nodeFolderPropertiesProvider() {
        return array(
            array('ObjectId','noderef')
        );
    }

    /**
     * Test the elis_files_process_node function with folder properties.
     * @dataProvider nodeFolderPropertiesProvider
     */
    public function testProcessNodeFolderProperties($field1,$field2) {

        $dom = $this->loadfolderDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');
        $xpath = new DOMXPath($dom);
        $properties    = $xpath->query('.//cmis:properties/*', $nodes->item(0));

        $j = 0;
        while ($prop = $properties->item($j++)) {
            $value = '';
            $propname = $prop->getAttribute('cmis:name');
            if($propname === $field1) {
                $value = $prop->nodeValue;
                break;
            }
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value,$contentNode->$field2);

    }

    public function nodeFilePropertiesProvider() {
        return array(
            array('ContentStreamLength','filesize'),
            array('ContentStreamMimeType','filemimetype'),
            array('ContentStreamFilename','filename'),
            array('ContentStreamUri','fileurl'),
            array('ObjectId','noderef')
        );
    }

    /**
     * Test the elis_files_process_node function with file properties.
     * @dataProvider nodeFilePropertiesProvider
     */
    public function testProcessNodeFileProperties($field1,$field2) {

        $dom = $this->loadfileDOMfromXML();

        $nodes = $dom->getElementsByTagName('entry');
        $xpath = new DOMXPath($dom);
        $properties    = $xpath->query('.//cmis:properties/*', $nodes->item(0));

        $j = 0;
        while ($prop = $properties->item($j++)) {
            $value = '';
            $propname = $prop->getAttribute('cmis:name');
            if($propname === $field1) {
                $value = $prop->nodeValue;
                break;
            }
        }

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value,$contentNode->$field2);

    }

    /**
     * Test the elis_files_process_node function with cmis:name not set for the cmis property.
     */
    public function testProcessNodeNoCmisName() {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $string = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">'.
        '<author><name>admin</name></author>'.
        '<generator version="3.2.1 (.2 3)">Alfresco (Enterprise)</generator>'.
        '<icon>http://localhost:8080/alfresco/images/logo/AlfrescoLogo16.ico</icon>'.
        '<id>urn:uuid:fff6c376-bf63-4475-a25d-383251dcb3fb-parent</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb/parent?alf_ticket=TICKET_edd0417f8ba654b6c4ac3f7c4317fbae31b648e9"></link>'.
        '<link rel="source" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb"></link>'.
        '<title>Import_questions.txt Parent</title>'.
        '<updated>2012-03-02T15:52:24.790-05:00</updated>'.
        '<entry>'.
        '<author><name>System</name></author>'.
        '<content>d60de32a-9e4d-40c5-888a-08607fedf9f5</content>'.
        '<id>urn:uuid:d60de32a-9e4d-40c5-888a-08607fedf9f5</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="edit" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="allowableactions" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/permissions"></link>'.
        '<link rel="relationships" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/rels"></link>'.
        '<link rel="parents" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/parent"></link>'.
        '<link rel="children" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/children"></link>'.
        '<link rel="descendants" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/descendants"></link>'.
        '<link rel="type" href="http://localhost:8080/alfresco/s/api/type/folder"></link>'.
        '<link rel="repository" href="http://localhost:8080/alfresco/s/api/repository"></link>'.
        '<published>2012-02-17T10:49:07.215-05:00</published>'.
        '<summary>User Homes</summary><title>User Homes</title>'.
        '<updated>2012-02-17T10:49:07.230-05:00</updated>'.
        '<cmis:object>'.
            '<cmis:properties>'.
                '<cmis:propertyString><cmis:value>document</cmis:value></cmis:propertyString>'.
            '</cmis:properties>'.
        '</cmis:object>'.
        '<cmis:terminator></cmis:terminator>'.
        '</entry>'.
        '<cmis:hasMoreItems>false</cmis:hasMoreItems>'.
        '</feed>';
        $dom->loadXML($string);
        libxml_clear_errors();

        $nodes = $dom->getElementsByTagName('entry');

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentNode);
    }

    /**
     * Test the elis_files_process_node function with no nodevalue for the cmis property.
     */
    public function testProcessNodeNoNodeValue() {

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $string = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://www.w3.org/2007/app" xmlns:cmis="http://docs.oasis-open.org/ns/cmis/core/200901" xmlns:alf="http://www.alfresco.org" xmlns:opensearch="http://a9.com/-/spec/opensearch/1.1/">'.
        '<author><name>admin</name></author>'.
        '<generator version="3.2.1 (.2 3)">Alfresco (Enterprise)</generator>'.
        '<icon>http://localhost:8080/alfresco/images/logo/AlfrescoLogo16.ico</icon>'.
        '<id>urn:uuid:fff6c376-bf63-4475-a25d-383251dcb3fb-parent</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb/parent?alf_ticket=TICKET_edd0417f8ba654b6c4ac3f7c4317fbae31b648e9"></link>'.
        '<link rel="source" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/fff6c376-bf63-4475-a25d-383251dcb3fb"></link>'.
        '<title>Import_questions.txt Parent</title>'.
        '<updated>2012-03-02T15:52:24.790-05:00</updated>'.
        '<entry>'.
        '<author><name>System</name></author>'.
        '<content>d60de32a-9e4d-40c5-888a-08607fedf9f5</content>'.
        '<id>urn:uuid:d60de32a-9e4d-40c5-888a-08607fedf9f5</id>'.
        '<link rel="self" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="edit" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5"></link>'.
        '<link rel="allowableactions" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/permissions"></link>'.
        '<link rel="relationships" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/rels"></link>'.
        '<link rel="parents" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/parent"></link>'.
        '<link rel="children" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/children"></link>'.
        '<link rel="descendants" href="http://localhost:8080/alfresco/s/api/node/workspace/SpacesStore/d60de32a-9e4d-40c5-888a-08607fedf9f5/descendants"></link>'.
        '<link rel="type" href="http://localhost:8080/alfresco/s/api/type/folder"></link>'.
        '<link rel="repository" href="http://localhost:8080/alfresco/s/api/repository"></link>'.
        '<published>2012-02-17T10:49:07.215-05:00</published>'.
        '<summary>User Homes</summary><title>User Homes</title>'.
        '<updated>2012-02-17T10:49:07.230-05:00</updated>'.
        '<cmis:object>'.
            '<cmis:properties>'.
                '<cmis:propertyString cmis:name="BaseType"></cmis:propertyString>'.
            '</cmis:properties>'.
        '</cmis:object>'.
        '<cmis:terminator></cmis:terminator>'.
        '</entry>'.
        '<cmis:hasMoreItems>false</cmis:hasMoreItems>'.
        '</feed>';
        $dom->loadXML($string);
        libxml_clear_errors();

        $nodes = $dom->getElementsByTagName('entry');

        $contentNode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentNode);
    }
}
