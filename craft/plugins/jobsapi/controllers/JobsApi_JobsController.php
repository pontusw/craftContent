<?php
namespace Craft;

class JobsApi_JobsController extends BaseController
{
    public function actionSaveJob()
    {
       //echo "save";
       Craft()->jobsApi_jobs->saveJob();
    }

    public function actionDeleteIngredient()
    {
       
    }
}
?>