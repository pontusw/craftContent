<?php
namespace Craft;

class JobsApi_JobsService extends BaseApplicationComponent
{
    public function saveJob()
    {

		$entry = new EntryModel();
		$entry->sectionId = 20;
		$entry->typeId    = 20;
		$entry->authorId  = 1;
		$entry->enabled   = true;

		$entry->getContent()->title = "Hello World!";
		$entry->getContent()->jobsTitle = "MFCEO";
		$entry->getContent()->jobsDescription = "Do som work!";

		$success = craft()->entries->saveEntry($entry);
		

		if (!$success)
		{
		    Craft::log('Couldn’t save the entry "'.$entry->title.'"', LogLevel::Error);
		}

    }

    public function deleteJobById($id)
    {
       echo $id;
    }
}