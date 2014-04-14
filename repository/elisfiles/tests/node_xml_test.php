<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2013 onwards Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    repository_elisfiles
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once(dirname(__FILE__).'/../../../local/eliscore/test_config.php');
require_once($CFG->dirroot.'/local/eliscore/lib/setup.php');
require_once($CFG->dirroot.'/repository/elisfiles/ELIS_files_factory.class.php');
require_once($CFG->dirroot.'/repository/elisfiles/lib/lib.php');

/**
 * Tests Node XML
 * @group repository_elisfiles
 */
class repository_elisfiles_file_node_xml_testcase extends elis_database_test {
    /**
     * A function to return initial node setup data
     * @return object initial file node data
     */
    public function setup_node() {
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

    /**
     * A function to setup an expected node structure
     * @param object $node a file node object
     * @return object a node data structure
     */
    public function setup_expected_content($node) {
        $expectedcontentnode = new stdClass;
        $expectedcontentnode->uuid    = str_replace('urn:uuid:', '', $node->uuid);
        $expectedcontentnode->summary = ''; // Not returned with CMIS data
        $expectedcontentnode->title   = $node->properties['cmis:name'];
        $expectedcontentnode->icon    = '';
        $expectedcontentnode->type    = $node->properties['cmis:baseTypeId'];

        return $expectedcontentnode;
    }

    /**
     * Test the elis_files_process_node_new function that uuid, summary, title, icon and type are set.
     */
    public function test_process_node_new_validate_base_fields() {
        $node = $this->setup_node();

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);

        // assert that the base fields were set
        $this->assertEquals($expectedcontentnode->uuid, $contentnode->uuid);
        $this->assertEquals($expectedcontentnode->summary, $contentnode->summary);
        $this->assertEquals($expectedcontentnode->title, $contentnode->title);
        $this->assertEquals($expectedcontentnode->icon, $contentnode->icon);
        $this->assertEquals($expectedcontentnode->type, $contentnode->type);
    }

    /**
     * A data provider to return ownership test data
     * @return array an array of an array of strings
     */
    public function owner_provider() {
        return array(
                array('cmis:lastModifiedBy', 'System'),
                array('cmis:createdBy', 'Moodleadmin'),
                array(null, 'none')
        );
    }

    /**
     * Test the elis_files_process_node_new function that all possible values of owner are handled.
     * @dataProvider owner_provider
     * @param string $field node property
     * @param string $data value of the property
     */
    public function test_process_node_new_owner($field, $data) {
        $node = $this->setup_node();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);
        $expectedcontentnode->owner = $data;

        $this->assertEquals($expectedcontentnode->owner, $contentnode->owner);
    }

    /**
     * A data provider method to return file fields test data
     * @return array an array of an array of strings
     */
    public function file_fields_provider() {
        return array(
                array('cmis:contentStreamFileName', 'filename', 'test.png'),
                array('cmis:contentStreamLength', 'filesize', '3434'),
                array('cmis:contentStreamMimeType', 'filemimetype', 'image/png')
        );
    }

    /**
     * Test the elis_files_process_node_new function that file fields are processed.
     * @dataProvider file_fields_provider
     * @param string $field1 a node property
     * @param string $field2 a node property
     * @param string $field2 a node property value
     */
    public function test_process_node_new_file_fields($field1, $field2, $data) {
        $node = $this->setup_node();
        // test the 3 different owners
        $node->properties[$field1] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);
        $expectedcontentnode->$field2 = $data;

        $this->assertEquals($expectedcontentnode->$field2, $contentnode->$field2);
    }

    /**
     * A data provider method to return file created test data
     * @return array an array of an array of strings
     */
    public function created_provider() {
        return array(
                array('cmis:creationDate', '2012-01-20T13:46:00.038-05:00'),
                array('cmis:creationDate', '2012-01-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the created time is processed.
     * @dataProvider created_provider
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_new_created($field, $data) {
        $node = $this->setup_node();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);
        // recreate created time
        $expectedcontentnode->created = strtotime($data);

        $this->assertEquals($expectedcontentnode->created, $contentnode->created);
    }

    /**
     * A data provider method to return file updated test data
     * @return array an array of an array of strings
     */
    public function updated_provider() {
        return array(
            array('cmis:lastModificationDate', '2012-02-20T13:46:00.038-05:00'),
            array('cmis:lastModificationDate', '2012-02-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the updated time is processed.
     * @dataProvider updated_provider
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_new_updated($field, $data) {
        $node = $this->setup_node();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);
        // recreate updated time
        $expectedcontentnode->modified = strtotime($data);

        $this->assertEquals($expectedcontentnode->modified, $contentnode->modified);
    }

    /**
     * A data provider method to return links property data
     * @return array an array of an array of strings
     */
    public function link_fields_provider() {
        return array(
                array('down', 'children', 'http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/children'),
                array('down-tree', 'descendants', 'http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/e54d46e5-f047-4c6c-a39e-258d1370671c/descendants'),
                array('describedby', 'type', 'http://localhost:8080/alfresco/s/cmis/type/cmis:folder'),
                array('edit-media', 'fileurl', 'http://localhost:8080/alfresco/s/cmis/s/workspace:SpacesStore/i/3747b3f4-2e4c-4e8d-8e67-e2348fd65486/content.png')
        );
    }

    /**
     * Test the elis_files_process_node_new function that the link fields are processed.
     * @dataProvider link_fields_provider
     * @param string $field a node property
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_new_link_fields($field1, $field2, $data) {
        $node = $this->setup_node();
        // test the 4 link fields
        $node->links[$field1] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);
        // recreate the 4 link fields
        if ($field1 != 'edit-media') {
            $expectedcontentnode->links[$field2] = $data;
            $this->assertEquals($expectedcontentnode->links[$field2], $contentnode->links[$field2]);
        } else {
            $expectedcontentnode->$field2 = $data;
            $this->assertEquals($expectedcontentnode->$field2, $contentnode->$field2);
        }
    }

    /**
     * A data provider method to return node property test data
     * @return array an array of an array of strings
     */
    public function node_new_properties_provider() {
        return array(
                array('cmis:objectId', 'workspace://SpacesStore/fab64d29-99a2-4ab2-b9ef-f231bf7e2559')
        );
    }

    /**
     * Test the elis_files_process_node_new function with objectid property.
     * @dataProvider node_new_properties_provider
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_new_noderef($field, $data) {
        $node = $this->setup_node();
        // test the 3 different owners
        $node->properties[$field] = $data;

        $contentnode = elis_files_process_node_new($node, $type);

        $expectedcontentnode = $this->setup_expected_content($node);

        $this->assertEquals($data, $contentnode->noderef);

    }

    /**
     * This method loads an XML document
     * @return string an XML document
     */
    public function load_folder_dom_from_xml() {
        global $CFG;

        $dom = new DOMDocument();
        // load into dom
        $dom->load($CFG->dirroot.'/repository/elisfiles/tests/fixtures/folderresponse.xml');
        if (!$dom) {
            $this->markTestIncomplete('Could not parse DOM object from '.$CFG->dirroot.
                    '/repository/elisfiles/tests/fixtures/folderresponse.xml');
        }
        return $dom;
    }

    /**
     * This method loads an XML document
     * @return string an XML document
     */
    public function load_file_dom_from_xml() {
        global $CFG;

        $dom = new DOMDocument();
        // load into dom
        $dom->load($CFG->dirroot.'/repository/elisfiles/tests/fixtures/fileresponse.xml');
        if (!$dom) {
            $this->markTestIncomplete('Could not parse DOM obhect from '.$CFG->dirroot.
                    '/repository/elisfiles/tests/fixtures/fileresponse.xml');
        }
        return $dom;
    }

    /**
     * Test the elis_files_process_node function with an empty node.
     */
    public function test_process_node_no_child_nodes() {
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

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);

        // leave as is and fix code - initialise contentnode to false or something
        $this->assertFalse($contentnode);
    }

    /**
     * Test the elis_files_process_node function with base fields id and author.
     */
    public function test_process_node_owner_and_uuid() {
        $dom = $this->load_folder_dom_from_xml();
        $xpath = new DOMXpath($dom);

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $id = $node->getElementsByTagName('id');
            $id = str_replace('urn:uuid:', '', $id->item(0)->nodeValue);
            $author = $node->getElementsByTagName("author")->item(0)->nodeValue;
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals($id, $contentnode->uuid);
        $this->assertEquals($author, $contentnode->owner);
    }

    /**
     * A data provider method to return node created test data
     * @return array an array of an array of strings
     */
    public function node_created_provider() {
        return array(
                array('published', '2012-01-20T13:46:00.038-05:00'),
                array('published', '2012-01-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node function with the creation date.
     * @dataProvider node_created_provider
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_created($field, $data) {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $published = $node->getElementsByTagName($field);
            $published->item(0)->nodeValue = $data;
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals(strtotime($data), $contentnode->created);
    }

    /**
     * A data provider method to return node updated test data
     * @return array an array of an array of strings
     */
    public function node_updated_provider() {
        return array(
                array('updated', '2012-01-20T13:46:00.038-05:00'),
                array('updated', '2012-01-20T13:30:00.038-05:30')
        );
    }

    /**
     * Test the elis_files_process_node function with the creation date.
     * @dataProvider node_updated_provider
     * @param string $field a node property
     * @param string $data a node property value
     */
    public function test_process_node_updated($field, $data) {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $updated = $node->getElementsByTagName($field);
            $updated->item(0)->nodeValue = $data;
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals(strtotime($data), $contentnode->modified);
    }

    /**
     * Test the elis_files_process_node function with the title, summary and icon.
     */
    public function test_process_node_title_summary_icon() {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $title = $node->getElementsByTagName('title')->item(0)->nodeValue;
            $summary = $node->getElementsByTagName('summary')->item(0)->nodeValue;
            // with a colon in the tag name, just use the second part of the name
            $icon = $node->getElementsByTagName('icon')->item(0)->nodeValue;
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);

        $this->assertEquals($title, $contentnode->title);
        $this->assertEquals($summary, $contentnode->summary);
        $this->assertEquals($icon, $contentnode->icon);
    }

    /**
     * A data provider method to return node link fields test data
     * @return array an array of an array of strings
     */
    public function nodelink_fields_provider() {
        return array(
                array('self', 'self'),
                array('allowableactions', 'permissions'),
                array('relationships', 'associations'),
                array('parents', 'parent'),
                array('children', 'children'),
                array('descendants', 'descendants'),
                array('type', 'type')
        );
    }

    /**
     * Test the elis_files_process_node function with links.
     * @dataProvider nodelink_fields_provider
     * @param string $field a node property
     * @param string $field2 a node property
     */
    public function test_process_node_links($field1, $field2) {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        foreach ($nodes as $node) {
            $value = '';
            $links = $node->getElementsByTagName('link');
            foreach ($links as $link) {
                if ($link->getAttribute('rel') === $field1) {
                    $value = str_replace(elis_files_base_url(), '', $link->getAttribute('href'));
                    break;
                }
            }
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value, $contentnode->links[$field2]);
    }

    /**
     * Test the elis_files_process_node function with no cmis properties.
     */
    public function test_process_node_no_cmis_properties() {
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

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentnode);
    }

    /**
     * Test the elis_files_process_node function with basetype folder.
     */
    public function test_process_node_folder_base_type() {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        $type = '';

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $elistypefolderparts = explode(':', ELIS_files::$type_folder, 2);
        $elistypefolder = isset($elistypefolderparts[1]) ? $elistypefolderparts[1] : ELIS_files::$type_folder;
        $this->assertEquals($elistypefolder, $type);
    }

    /**
     * Test the elis_files_process_node function with basetype document.
     */
    public function test_process_node_document_base_type() {
        $dom = $this->load_file_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');

        $type = '';

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $elistypedocumentparts = explode(':', ELIS_files::$type_document, 2);
        $elistypedocument = isset($elistypedocumentparts[1]) ? $elistypedocumentparts[1] : ELIS_files::$type_document;
        $this->assertEquals($elistypedocument, $type);
    }

    /**
     * A data provider method to return node folder properties test data
     * @return array an array of an array of strings
     */
    public function node_folder_properties_provider() {
        return array(
                array('ObjectId', 'noderef')
        );
    }

    /**
     * Test the elis_files_process_node function with folder properties.
     * @dataProvider node_folder_properties_provider
     * @param string $field a node property
     * @param string $field2 a node property
     */
    public function test_process_node_folder_properties($field1, $field2) {
        $dom = $this->load_folder_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');
        $xpath = new DOMXPath($dom);
        $properties    = $xpath->query('.//cmis:properties/*', $nodes->item(0));

        $j = 0;
        while ($prop = $properties->item($j++)) {
            $value = '';
            $propname = $prop->getAttribute('cmis:name');
            if ($propname === $field1) {
                $value = $prop->nodeValue;
                break;
            }
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value, $contentnode->$field2);
    }

    /**
     * A data provider method to return node file properties test data
     * @return array an array of an array of strings
     */
    public function node_file_properties_provider() {
        return array(
                array('ContentStreamLength', 'filesize'),
                array('ContentStreamMimeType', 'filemimetype'),
                array('ContentStreamFilename', 'filename'),
                array('ContentStreamUri', 'fileurl'),
                array('ObjectId', 'noderef')
        );
    }

    /**
     * Test the elis_files_process_node function with file properties.
     * @dataProvider node_file_properties_provider
     * @param string $field a node property
     * @param string $field2 a node property
     */
    public function test_process_node_file_properties($field1, $field2) {
        $dom = $this->load_file_dom_from_xml();

        $nodes = $dom->getElementsByTagName('entry');
        $xpath = new DOMXPath($dom);
        $properties    = $xpath->query('.//cmis:properties/*', $nodes->item(0));

        $j = 0;
        while ($prop = $properties->item($j++)) {
            $value = '';
            $propname = $prop->getAttribute('cmis:name');
            if ($propname === $field1) {
                $value = $prop->nodeValue;
                break;
            }
        }

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertEquals($value, $contentnode->$field2);
    }

    /**
     * Test the elis_files_process_node function with cmis:name not set for the cmis property.
     */
    public function test_process_node_no_cmis_name() {
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

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentnode);
    }

    /**
     * Test the elis_files_process_node function with no nodevalue for the cmis property.
     */
    public function test_process_node_no_node_value() {
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

        $contentnode = elis_files_process_node($dom, $nodes->item(0), $type);
        $this->assertNotEmpty($contentnode);
    }
}
