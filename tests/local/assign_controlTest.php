<?php

namespace mod_externalassignment\local;

use PHPUnit\Framework\TestCase;
/**
 * Unit tests for class assign_control
 * @group mod_externalassignment
 */
class assign_controlTest extends TestCase {
    private $context;
    private $coursemodule;
    private $course;

    public function test__construct() {
        $assigncontrol = new assign_control($this->context, $this->coursemodule, $this->course);
        $this->assertNotNull($assigncontrol->get_context());
        $this->assertNotNull($assigncontrol->get_coursemodule());
        $this->assertNotNull($assigncontrol->get_course());
    }
}
