# Selenium from JSON

   ## Start library and get results
   
    $selenium = new Selenium('127.0.0.1');
    $play = new Selenium\Play($selenium, $playJson, $testJson, $withScreenShot = true);
    $play->play();

    $selenium->stop();
    
   ### Generate JSON
   
   #### PlayJson
   
   - The play JSON can be generated with the chrome extension for chrome available here https://www.seleniumhq.org/download/
   - The JSON generated can be directly send here
        - ! We handle only the following event : open, click, select, type, selectFrame, runScript
        - ! We handle these selector: id, css (=css selector, not the class), name, linkText, xpatch
        
   - Example of a JSON
        - If youw ant to generate it by yourself, we need these main key: url, test.commands.command, test.commands.target, test.commands.value
   
        
            {
              "id": "85965227-ebc5-4c5a-8845-4ee0592b4bcc",
              "version": "1.1",
              "name": "Untitled Project",
              "url": "https://dillenbourg.be",
              "tests": [{
                "id": "c6d66c1b-6650-40e0-b238-da302b674599",
                "name": "Untitled",
                "commands": [{
                  "id": "a1fb6886-949d-417f-8e5a-4344ebb1e6e2",
                  "comment": "",
                  "command": "open",
                  "target": "/",
                  "targets": [],
                  "value": ""
                }, {
                  "id": "d247b938-d99b-4e9f-ab7a-95a211e8aa27",
                  "comment": "",
                  "command": "click",
                  "target": "linkText=Interactive version",
                  "targets": [
                    ["linkText=Interactive version", "linkText"],
                    ["xpath=//a[contains(text(),'Interactive version')]", "xpath:link"],
                    ["xpath=//a[contains(@href, 'command')]", "xpath:href"],
                    ["xpath=//a", "xpath:position"]
                  ],
                  "value": ""
                }, {
                  "id": "f58cafc0-3e39-42d9-a3a8-73247813cd6f",
                  "comment": "",
                  "command": "editContent",
                  "target": "css=#command-line-2 > span.highlighting_white",
                  "targets": [
                    ["css=#command-line-2 > span.highlighting_white", "css"],
                    ["xpath=//p[@id='command-line-2']/span[3]", "xpath:idRelative"],
                    ["xpath=//p[3]/span[3]", "xpath:position"]
                  ],
                  "value": "cat"
                }, {
                  "id": "88a8e230-3f29-48e7-bde6-6749b1c705b7",
                  "comment": "",
                  "command": "editContent",
                  "target": "css=#command-line-3 > span.highlighting_white",
                  "targets": [
                    ["css=#command-line-3 > span.highlighting_white", "css"],
                    ["xpath=//p[@id='command-line-3']/span[3]", "xpath:idRelative"],
                    ["xpath=//p[5]/span[3]", "xpath:position"]
                  ],
                  "value": "gif"
                }]
              }],
              "suites": [{
                "id": "dd50d3df-ec78-4d90-80a7-627bf73a1df6",
                "name": "Default Suite",
                "persistSession": false,
                "parallel": false,
                "timeout": 300,
                "tests": ["c6d66c1b-6650-40e0-b238-da302b674599"]
              }],
              "urls": ["https://dillenbourg.be/"],
              "plugins": []
            }     
    
   #### TestJson
   
   - The test JSON is a Json defining a list of test to execute at each step of the playJson.
   - Step start counting at 1 and a test JSON can look like that
   
        [
            1 => [
                [
                    'operation'     => 'page_title',
                    'target_prefix' => null,
                    'target'        => null,
                    'expected'      => 'dillenbourg'
                ],
                [
                    'operation'     => 'target_content_match',
                    'target_prefix' => 'css=',
                    'target'        => 'a.myfirstlink',
                    'expected'      => 'click here'
                ]
            ],
            2   => [
                ...
            ]
        
        ]
   - target_prefix can be one of these
  
      | Prefix | target value |
      | :---: | :---: |
      | css= | any valid css selector |    
      | id= | Any ID in the page |    
      | name= | Name of an attribute |    
      | linkText= | Looking by the text on a link (first one is matched) |    
      | xpath= | any valid Xpath | 
        
   - Operations can be
   
       | Operations | info |
       | :---: | :---: |
       | page_title | check if the title of the page is the one in "expected" |    
       | target_exists | Check if the target exists |    
       | target_content | Check if the target has the content in expected |
       | target_content_match | Check if the target match the content in expected. A regex is expected |
       | count_target_element | Check if the element is at least "expected" times in the page |
       | link_valid | Check if the link of the target is valid (HTTP status 200)|
           
  