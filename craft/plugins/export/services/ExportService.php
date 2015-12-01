<?php
namespace Craft;

class ExportService extends BaseApplicationComponent 
{

    protected $delimiter = ExportModel::DelimiterComma;

    public function download($settings) 
    {
    
        // Get max power
        craft()->config->maxPowerCaptain();
        
        // Create the export template
        $export = "";
        
        // Get data
        $data = $this->getData($settings);
        
        // If there is data, process
        if(count($data)) {
        
            // Count rows
            $rows = 0;
            
            // Loop trough data
            foreach($data as $element) {
            
                $row = "";
                
                // Get fields
                $fields = $this->parseFields($settings['map'], $element);
                
                // Put down columns
                if(!$rows) {
                    $row .= $this->parseColumns($settings['entrytype'], $fields);
                }
            
                // Loop trough the fields
                foreach($fields as $handle => $data) {
                    
                    // Parse element data
                    $data = $this->parseElementData($handle, $data);
                    
                    // Parse field data
                    $data = $this->parseFieldData($handle, $data);
                
                    // Put in quotes and escape
                    $row .= '"'.addslashes($data).'"'.$this->delimiter;
                
                }
                
                // Remove last comma
                $row = substr($row, 0, -1);
                
                // Encode row
                $row = mb_convert_encoding(utf8_decode($row), "Windows-1252", "UTF-8");
                
                // And start a new line
                $row = $row . "\r\n";
                
                // Append to data
                $export .= $row;
                
                // Count rows
                $rows++;
            
            }
                        
            // Download the csv
            craft()->request->sendFile('export.csv', $export, array('forceDownload' => true));
            //echo $export;
                
        }
        
        // There's nothing beyond the export
        craft()->end();
    
    }
    
    protected function getData($settings) 
    {
    
        // Get other sources
        $sources = craft()->plugins->call('registerExportSource', array($settings));
                        
        // If no sources, get data by ourselves
        if(!count($sources) || in_array(false, $sources)) {
        
            // Find data
            eval("\$elementType = Craft\\ElementType::".$settings['type'].";");
            $criteria = craft()->elements->getCriteria($elementType);
            $criteria->limit = null;
            $criteria->status = isset($settings['map']['status']) ? $settings['map']['status'] : null;
            
            // Entry specific data
            if($settings['type'] == ElementType::Entry) {
                $criteria->sectionId = $settings['section'];
                $criteria->type = $settings['entrytype'];
            }
            
            // User specific data
            if($settings['type'] == ElementType::User) {
                $criteria->groupId = $settings['groups'];
            }
            
            // Gather data
            $data = $criteria->find();
        
        // Else proces the plugin's datasource
        } else {
        
            // Re-order hook output for use
            $data = array();
            foreach($sources as $plugin) {
                foreach($plugin as $source) {
                    $data[] = $source;
                }
            } 
        
        }
        
        return $data;
    
    }
    
    // Parse fields
    protected function parseFields($map, $element) 
    {
    
        $fields = array();
    
        // Only get element attributes and content attributes
        if($element instanceof BaseElementModel) {
            $attributes = array_merge($element->getAttributes(), $element->getContent()->getAttributes());
        } else {
            $attributes = $element;
        }
        
        // Loop through the map
        foreach($map as $handle => $checked) {
        
            // Only get checked fields
            if($checked == '1') {
            
                // Fill them with data
                $fields[$handle] = $attributes[$handle];
            
            }
        
        }
        
        return $fields;
    
    }
    
    // Parse column names
    protected function parseColumns($entrytype, $fields) 
    {
    
        $columns = "";
        
        // Loop trough fields
        foreach($fields as $handle => $data) {
        
            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);
            
            // If not a field...
            if(is_null($field)) {
            
                switch($handle) {
                
                    case ExportModel::HandleStatus:
                        $columns .= '"'.Craft::t("Status").'"'.$this->delimiter;
                        break;
                
                    # Entries
                    case ExportModel::HandleTitle:
                        $columns .= '"'.addslashes(craft()->sections->getEntryTypeById($entrytype)->titleLabel).'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleAuthor:
                        $columns .= '"'.Craft::t("Author").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandlePostDate:
                        $columns .= '"'.Craft::t("Post Date").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleExpiryDate:
                        $columns .= '"'.Craft::t("Expiry Date").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleEnabled:
                        $columns .= '"'.Craft::t("Enabled").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleSlug:
                        $columns .= '"'.Craft::t("Slug").'"'.$this->delimiter;
                        break;
                        
                    # Users
                    case ExportModel::HandleUsername:
                        $columns .= '"'.Craft::t("Username").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleFirstName:
                        $columns .= '"'.Craft::t("First Name").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleLastName:
                        $columns .= '"'.Craft::t("Last Name").'"'.$this->delimiter;
                        break;
                        
                    case ExportModel::HandleEmail:
                        $columns .= '"'.Craft::t("Email").'"'.$this->delimiter;
                        break;
                
                }
            
            } else {
            
                $columns .= '"'.addslashes($field->name).'"'.$this->delimiter;
            
            }
                    
        }
                
        // Remove last comma
        $columns = substr($columns, 0, -1);
        
        // Encode row
        $columns = mb_convert_encoding(utf8_decode($columns), "Windows-1252", "UTF-8");
        
        // And start a new line
        $columns = $columns . "\r\n";
        
        return $columns;
    
    }

    // Parse reserved element values
    protected function parseElementData($handle, $data) 
    {
    
        switch($handle) {
        
            case ExportModel::HandleAuthor:
                
                // Get username of author
                $data = craft()->users->getUserById($data)->username;
                                
                break;
                
            case ExportModel::HandleEnabled:
            
                // Make data human readable
                switch($data) {
                
                    case "1":
                        $data = Craft::t("Yes");
                        break;
                        
                    case "2":
                        $data = Craft::t("No");
                        break;
                
                }
            
                break;
        
        }
        
        return $data;
        
    }
    
    // Parse field values
    public function parseFieldData($handle, $data) 
    {
    
        // Do we have any data at all
        if(!is_null($data)) {
               
            // Get field info
            $field = craft()->fields->getFieldByHandle($handle);
           
            // If it's a field ofcourse
            if(!is_null($field)) {
               
                // For some fieldtypes the're special rules
                switch($field->type) {
               
                    case ExportModel::FieldTypeEntries:
                    case ExportModel::FieldTypeCategories:
                   
                        // Show title
                        $data = $data->first()->title;
                                           
                        break;
                        
                    case ExportModel::FieldTypeAssets:
                    
                        // Show filename
                        $data = $data->first()->filename;
                        
                        break;
                   
                    case ExportModel::FieldTypeUsers:
                   
                        // Show username
                        $data = $data->first()->username;
                                           
                        break;
                        
                    case ExportModel::FieldTypeLightswitch:
                    
                        // Make data human readable
                        switch($data) {
                        
                            case "1":
                                $data = Craft::t("Yes");
                                break;
                                
                            case "2":
                                $data = Craft::t("No");
                                break;
                        
                        }
                        
                        break;
               
                }
           
            }
            
        } else {
        
            // Don't return null, return empty
            $data = "";
        
        }
                               
        return $data;
   
    }

}