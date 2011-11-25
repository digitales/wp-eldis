<?php
/**
 *
 *
 *
 */

define('API_URL', 'http://api.ids.ac.uk/'); // Please include the trailing slash
define('API_FORMAT', 'json'); //This is the default format that API calls will use. At this time, only JSON (json) is fully implemented. XML implementation has started, but needs work, so it is disabled.
define('API_PAGE_SIZE', 10); 

/* Increase this if you want to get more results per request. Set this to 0 to get all available results on every request. You can also override this setting by calling $yourApiObject->setPageSize(<page size>); This is recommended to avoid rapid exhaustion of your API limit. You can page manually by calling $yourApiObject->request($offset), where $offset is the page to which you wish to jump.
// NOTE: Changing this option will cause you to reach your hourly limit for requests faster, and you may need to contact Meetup Developer Support to have it increased. */
define('API_NUM_PAGES', 1);

define('API_SORT_DESC', FALSE); //Set this to TRUE if you want your results in descending order by default. Note that some methods provide their own ordering capabilities.