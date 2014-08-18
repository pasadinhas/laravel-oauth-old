<?php namespace LaravelOAuth\Decorators\OAuth2;

class FenixEduDecorator extends BaseServiceDecorator
{
    private $_language;
    private $_academicTerm;

    protected function bootstrap()
    {
        $this->_language = 'en-GB';
    }

    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    public function filterAcademicTerm($filter)
    {
        $this->_academicTerm = $filter;
    }

    public function getAbout()
    {
        return $this->requestJSON("about");
    }

    public function getAcademicTerms()
    {
        return $this->requestJSON("academicterms");
    }

    public function getCanteen()
    {
        return $this->requestJSON("canteen");
    }

    public function getCourses()
    {
        return $this->requestJSON("courses");
    }
    
    public function getCourse($id)
    {
        return $this->requestJSON("courses/$id");
    }

    public function getCourseEvaluations($id)
    {
        return $this->requestJSON("courses/$id/evaluations");
    }

    public function getCourseGroups($id)
    {
        return $this->requestJSON("courses/$id/groups");
    }

    public function getCourseSchedule($id)
    {
        return $this->requestJSON("courses/$id/schedule");
    }

    public function getCourseStudents($id)
    {
        return $this->requestJSON("courses/$id/students");
    }

    public function getDegrees()
    {
        return $this->requestJSON("degrees");
    }

    public function getDegree($id)
    {
        return $this->requestJSON("degrees/$id");
    }

    public function getDegreeCourses($id)
    {
        return $this->requestJSON("degrees/$id/courses");
    }

    public function getDomainModel()
    {
        return $this->requestJSON("domainModel");
    }

    public function getPerson()
    {
        return $this->requestJSON("person");
    }

    public function getPersonCalendarClasses()
    {
        return $this->requestJSON("person/calendar/classes");
    }

    public function getPersonCalendarEvaluations()
    {
        return $this->requestJSON("person/calendar/evaluations");
    }

    public function getPersonCourses()
    {
        return $this->requestJSON("person/courses");
    }

    public function getPersonCurriculum()
    {
        return $this->requestJSON("person/curriculum");
    }

    public function getPersonEvaluations()
    {
        return $this->requestJSON("person/evaluations");
    }

    public function enrollPersonInEvaluation($id)
    {
        return $this->requestJSON("person/evaluations/$id?enrol=yes");
    }

    public function removePersonFromEvaluation($id)
    {
        return $this->requestJSON("person/evaluations/$id?enrol=no");
    }

    public function getPersonPayments()
    {
        return $this->requestJSON("person/payments");
    }

    public function getSpaces()
    {
        return $this->requestJSON("spaces");
    }

    public function getSpace($id)
    {
        return $this->requestJSON("spaces/$id");
    }

    public function getSpaceBlueprint($id)
    {
        return $this->requestJSON("spaces/$id/blueprint");
    }
}