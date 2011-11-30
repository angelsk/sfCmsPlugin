// Generate a route name and url based on the title of the sitetree node
var generateRoute = function (e)
{
  var titleEl     = $('sitetree_' + culture + '_title');
  var routeNameEl = $('sitetree_route_name');
  var baseUrlEl   = $('sitetree_base_url');
  
  var newTitle    = titleEl.value.toLowerCase().replace('&','and');
  newTitle        = newTitle.replace(/[^a-z0-9_-]/g, '-');
  newTitle        = newTitle.replace(/([-])+/g, '-');

  if (newTitle.substring(0, 1) == '-') 
  {
    newTitle = newTitle.substring(1);
  }
  
  if (newTitle.substring(newTitle.length - 1) == '-') 
  {
    newTitle = newTitle.substring(0, newTitle.length - 1);
  }
  
  routeNameEl.set('value', newTitle);
  baseUrlEl.set('value', newTitle);
};

$(document).addEvent('domready', function() 
{
  // Confirm sitetree deletion in case of accidental click
  $$('.delete_sitetree').each(function(el)
  {
    el.addEvent('click', function(e) 
    {
      return confirm('Are you sure you want to delete this page?');
    });
  });
  
  // For new items only
  if ($('sitetree_id') && '' == $('sitetree_id').get('value'))
  {
    $('sitetree_' + culture + '_title').addEvent('keyup', generateRoute);
  }
});
