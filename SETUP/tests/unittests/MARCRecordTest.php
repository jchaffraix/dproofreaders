<?php

/**
 * @backupGlobals disabled
 */
class MARCRecordTest extends PHPUnit\Framework\TestCase
{
    private $YAZ_ARRAY = null;

    protected function setUp(): void
    {
        // Load the yaz array from disk
        $yaz_array_b64 = file_get_contents("./data/yaz_array.b64");
        $this->YAZ_ARRAY = unserialize(base64_decode($yaz_array_b64));
    }

    private function _load_record(): MARCRecord
    {
        $marc_record = new MARCRecord();
        $marc_record->load_yaz_array($this->YAZ_ARRAY);
        return $marc_record;
    }

    public function testEmptyConstructor(): void
    {
        $marc_record = new MARCRecord();
        $this->assertEquals([], $marc_record->get_yaz_array());
    }

    public function testLoadYazArray(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals($this->YAZ_ARRAY, $marc_record->get_yaz_array());
    }

    public function testGetTitle(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals(
            'The backwoods boy; or, The boyhood and manhood of Abraham Lincoln.',
            $marc_record->title
        );
    }

    public function testGetAuthor(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('Alger, Horatio', $marc_record->author);
    }

    public function testGetLCCN(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('55055815', $marc_record->lccn);
    }

    public function testGetISBN(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('', $marc_record->isbn);
    }

    public function testGetPages(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('307 p.', $marc_record->pages);
    }

    public function testGetDate(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('[c1883', $marc_record->date);
    }

    public function testGetLanguage(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('English', $marc_record->language);
    }

    public function testGetLiteraryForm(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('', $marc_record->literary_form);
    }

    public function testGetSubject(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('', $marc_record->subject);
    }

    public function testGetDescription(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('', $marc_record->description);
    }

    public function testGetPublisher(): void
    {
        $marc_record = $this->_load_record();
        $this->assertEquals('Street and Smith, [c1883', $marc_record->publisher);
    }

    public function testAdditionalAuthors(): void
    {
        // A record that has no Main Entry author, and multiple Additional Entries.
        // Showing just the additional entry names:
        // [
        // ..
        //   ["(3,700)(3,1 )(3,a)", "Stewart, Maria W.,"],
        //   ["(3,700)(3,1 )(3,a)", "Garnet, Henry Highland,"],
        //   ["(3,700)(3,1 )(3,a)", "Douglass, Frederick,"],
        //   ["(3,700)(3,1 )(3,a)", "Washington, Booker T.,"],
        //   ["(3,700)(3,1 )(3,a)", "McNeil, Claudia,"],
        //   ["(3,700)(3,1 )(3,a)", "Matlock, Norman,"],
        //   ["(3,700)(3,1 )(3,a)", "Graham, John,"],
        // ..
        // ]
        $yaz_array = unserialize(base64_decode(file_get_contents("./data/yaz_array_added_names.b64")));
        $marc_record = new MARCRecord();
        $marc_record->load_yaz_array($yaz_array);
        $authors = implode(" & ", [
            "Stewart, Maria W.",
            "Garnet, Henry Highland",
            "Douglass, Frederick",
            "Washington, Booker T.",
            "McNeil, Claudia",
            "Matlock, Norman",
            "Graham, John",
        ]);
        $this->assertEquals($authors, $marc_record->author);
    }

    public function testUnnamedPublisher(): void
    {
        // A record whose publisher subfields have a location and date, but not a name
        // [
        // ..
        //   ["(3,260)"],
        //   ["(3,260)(3,  )"],
        //   ["(3,260)(3,  )(3,a)", "[Washington?"],
        //   ["(3,260)(3,  )(3,c)", "1890?]"],
        // ..
        // ]
        $yaz_array = unserialize(base64_decode(file_get_contents("./data/yaz_array_no_publisher_name.b64")));
        $marc_record = new MARCRecord();
        $marc_record->load_yaz_array($yaz_array);
        $this->assertEquals("1890?", $marc_record->publisher);
    }
}
