var Movies = {
  config: {
    tableId: 'main',
    actionsBoxId: 'actions',
    footerId: 'footer',
    columnOffsetTitle: 0,
    columnOffsetCrew: 1,
    columnOffsetCast: 2,
    columnOffsetGenre: 3,
    columnOffsetYear: 4,
    columnOffsetRuntime: 5,
    columnOffsetRating: 6,
    genres: ['Action','Adventure','Animation','Biography','Comedy','Crime',
      'Documentary','Drama','Family','Fantasy','Film-Noir','Game-Show','History',
      'Horror','Music','Musical','Mystery','News','Reality-TV','Romance','Sci-Fi',
      'Sport','Talk-Show','Thriller','War','Western'],
    imdbIds: ''
  },
  
  
  init: function() {
    // All the rows in the tablebody
    Movies.tableElm = elm(Movies.config.tableId);
    Movies.trElms = Movies.tableElm.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    Movies.actionsBoxElm = elm(Movies.config.actionsBoxId);
    
    // Zebrastripe the table
    Movies.applyOddEven();
    
    Movies.applyImdb();
  },
  
  // Add an element to the actions div
  addAction: function(elm) {
    Movies.actionsBoxElm.appendChild(elm);
    // add spaces between elements for word-wrapping
    Movies.actionsBoxElm.appendChild(document.createTextNode(' '));
  },
  
  
  // Zebra stripes the table
  // Accepts an optional argument: a table that needs to be striped
  applyOddEven: function(table) {
    var trElms = table ? table.getElementsByTagName('tr') : Movies.trElms;
    
    var even = true;
    for (var i = 0; trElms[i]; ++i) {
      removeClass(trElms[i], 'even');
      if (!hasClass(trElms[i], 'hide')) {
        even = !even;
        if (even)
          addClass(trElms[i], 'even');
      }
    }
  },
  
  
  // Adds IMDb top 250 ratings to the table
  applyImdb: function() {
    for (var i = 0; Movies.trElms[i]; ++i) {
      var tdElm = Movies.trElms[i].getElementsByTagName('td')[Movies.config.columnOffsetTitle];
      var aElm = tdElm.getElementsByTagName('a')[0];
      var offset = Movies.config.imdbIds.indexOf('|' + aElm.href.substring(28, 35) + '|');
      
      if (aElm && offset != -1) {
        var strongElm = document.createElement('strong');
          strongElm.appendChild(document.createTextNode(Math.floor((offset + 8) / 8)));
          tdElm.appendChild(document.createTextNode(' '));
          tdElm.appendChild(strongElm);
      }
    }
  },
  
  
  setActivePanel: function(elm) {
    if (Movies.activePanel && elm != Movies.activePanel) {
      addClass(Movies.activePanel, 'hide');
      
      if (Compare && Movies.activePanel.id == Compare.config.compareBoxId) {
        removeClass(Movies.tableElm, 'hide');
        addClass(Compare.sameTable, 'hide');
        addClass(Compare.otherTable, 'hide');
      }
    }
    removeClass(elm, 'hide');
    Movies.activePanel = elm;
  }
};

DOMReady(Movies.init);