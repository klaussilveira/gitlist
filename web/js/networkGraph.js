/**
 * Network Graph JS
 * This File is a part of the GitList Project at http://gitlist.org
 *
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author Lukas Domnick <lukx@lukx.de> http://github.com/lukx
 */
$( function() {

	// initialise network graph only when there is one network graph container on the page
	if( $('div.network-graph').length !== 1 ) {
		return;
	}

	var

	cfg = {
		laneColors: ['#ff0000', '#0000FF', '#00FFFF', '#00FF00', '#FFFF00', '#ff00ff'],
		laneHeight: 20,
		columnWidth: 42,
		dotRadius: 3
	},

	// the table element into which we will render our graph
	commitsGraph = $('div.network-graph').first(),
	nextPage = commitsGraph.data('source'),

	refreshButton = $('<button class="btn btn-small"></button>').insertAfter(commitsGraph.parent('div')),
	paper = Raphael( commitsGraph[0], commitsGraph.width(), commitsGraph.height()),
	usedColumns = 0
	;

	window.pap = paper;

	function fetchCommitData( url ) {
		console.log('Starting to fetch commit data from ', url);
		setRefreshButtonState(true);
		$.ajax({
			dataType: "json",
			url: url,
			success: handleNetworkDataLoaded,
			error: handleNetworkDataError
		});
	}


	function setRefreshButtonState( isCurrentlyLoading ) {
		var newInner = '<i class="icon-repeat"></i> Load more';
		if( isCurrentlyLoading ) {
			newInner = '<i class="icon-refresh"></i> Loading...';
		}

		refreshButton.html(newInner);
	};

	function refreshButtonClickHandler() {
		fetchCommitData(nextPage);
	};


	function handleNetworkDataLoaded( data ) {
		setRefreshButtonState(false);
		console.log('Retreived Commit Data', data);

		// store the next page as gotten from pagination
		nextPage = data.nextPage;

		// no commits or empty commits array? Well, we can't draw a graph of that
		if( !data.commits || data.commits.length < 1 ) {
			handleNoAvailableData();
			return;
		}

		prepareCommits( data.commits );
		renderCommits( data.commits );
	}

	function handleNetworkDataError( err ){
		setRefreshButtonState(false);
		console.log(err);
	}

	function handleNoAvailableData() {
		console.log('No Data available');
	}

	var parentsBeingWaitedFor = {},
		occupiedLanes = [],
		maxLanes = 0;

	function prepareCommits( commits ) {
		$.each( commits, function ( index, commit) {
			prepareCommit( commit );
		});
	}

	function findFreeLane() {
		var i = 0;

		while( true ) {
			// if an array index is not yet defined or set to false, the lane with that number is free.
			if( !occupiedLanes[i] ) {
				return i;
			}
			i ++;
		}
	}

	function prepareCommit( commit ) {
		// make "date" an actual JS Date object
		commit.date = new Date(commit.date*1000);

		// the parents will be filled once they have become rendered

		commit.parents = [];
		// get children for this commit
		commit.children = [];
		if( parentsBeingWaitedFor.hasOwnProperty( commit.hash )) {
			// there are child commits waiting
			commit.children = parentsBeingWaitedFor[commit.hash];

			// let the children know their parent objects
			$.each( commit.children, function(key, thisChild ) {
				thisChild.parents.push( commit );
			});

			// remove this item from parentsBeingWaitedFor
			delete parentsBeingWaitedFor[commit.hash];
		}

		commit.isFork  = ( commit.children.length > 1 );
		commit.isMerge = ( commit.parentsHash.length > 1 );

		// after a fork, the occupied lanes must be cleaned up. The children used some lanes we no longer occupy
		if (commit.isFork === true ) {
			$.each( commit.children, function( key, thisChild ) {
				// free this lane
				occupiedLanes[thisChild.lane.number] = false;
			});
		}

		// find out which lane we're on. Start with a free one
		var laneNumber = findFreeLane();

		// if the child is a merge, we need to figure out which lane we may render this commit on.
		// Rules are simple: A "parent" by the same author as the merge may render on the same line as the parent
		// others take the next free lane.
		if( commit.children.length > 0) {
			if( commit.children[0].isMerge && commit.children[0].author.email === commit.author.email ) {
				console.log('same author, same lane', commit);
				laneNumber = commit.children[0].lane.number;
				// furthermore, commits in a linear line of events may stay on the same lane, too
			} else if ( !commit.children[0].isMerge ) {
				console.log('Taking the childs lane because it was not a merge', commit);
				laneNumber = commit.children[0].lane.number;
			}
		}

		commit.lane = getLaneInfo( laneNumber );

		// now the lane we chose must be marked occupied again.
		occupiedLanes[commit.lane.number] = true;
		maxLanes = Math.max( occupiedLanes.length, maxLanes);

		// This commit's parents are not on stage yet, as we are rendering following the time line.
		// Therefore we are registering this commit as "waiting" for each of the parent hashes

		$.each( commit.parentsHash, function( key, thisParentHash ) {
			// iterating over the rendered commit's parent hashes...
			// parent hash should always be a string, but although I can't imagine a reason why it shouldn't,
			// let's just clear out the case where it is a complete commit object...

			// If parentsBeingWaitedFor does not already have a key for thisParent's hash, initialise as array
			if( !parentsBeingWaitedFor.hasOwnProperty(thisParentHash) ) {
				parentsBeingWaitedFor[thisParentHash] = [];
			}

			// allright, now register the commit that is currently being rendered with the parent queue
			parentsBeingWaitedFor[ thisParentHash ].push( commit );
		});

	}

	var lastRenderedDate = new Date(0);
	function renderCommits( commits ) {
		var neededWidth = ((usedColumns + Object.keys(commits).length) * cfg.columnWidth);
		if (  neededWidth > paper.width ) {
			console.log(neededWidth);
			extendPaper( neededWidth, paper.height  );


		} else {
			console.log( paper.width, neededWidth);
		}

		$.each( commits, function ( index, commit) {
			if( lastRenderedDate.getYear() !== commit.date.getYear()
				|| lastRenderedDate.getMonth() !== commit.date.getMonth()
				|| lastRenderedDate.getDate() !== commit.date.getDate() ) {
				// insert date row
			}
			renderCommit(commit);
			lastRenderedDate = commit.date;
		});
	}

	function extendPaper( newWidth, newHeight ) {
		var deltaX = newWidth - paper.width;

		paper.setSize( newWidth, newHeight );
		// fixup parent's scroll position
		paper.canvas.parentNode.scrollLeft = paper.canvas.parentNode.scrollLeft + deltaX;

		// now fixup the x position

		paper.forEach( function( el ) {

			if( el.type === "circle" ) {
				el.attr('cx', el.attr('cx') + deltaX);
			} else if ( el.type === "path") {
				var newXTranslation = el.data('currentXTranslation') || 0;
				newXTranslation += deltaX;
				el.transform( 't' + newXTranslation + ' 0' );
				el.data('currentXTranslation', newXTranslation);
			}
		});
	}

	function renderCommit( commit ) {
		// find the column this dot is drawn on
		usedColumns++;
		commit.column = usedColumns;

		// now the parent with the highest lane number determines whether we need more space to the right...


		commit.dot = paper.circle( getXPositionForColumnNumber(commit.column), commit.lane.centerY, cfg.dotRadius );
		commit.dot.attr({
			fill: commit.lane.color,
			stroke: 'none',
			cursor: 'pointer'
		})
			.data('commit', commit)
			.click( dotClickHandler );

		$.each( commit.children, function ( idx, thisChild ) {
			// if there is one child only, stay on the commit's lane as long as possible when connecting the dots.
			// but if there is more than one child, switch to the child's lane ASAP.
			// this is to display merges and forks where they happen (ie. at a commit node/ a dot), rather than
			// connecting from a line.
			// So: commit.isFork decides whether or not we must switch lanes early

			connectDots( commit, thisChild, commit.isFork );
		});
	}

	/**
	 *
	 * @param firstCommit
	 * @param secondCommit
	 * @param switchLanesEarly (boolean): Move the line to the secondCommit's lane ASAP? Defaults to false
	 */
	function connectDots( firstCommit, secondCommit, switchLanesEarly ) {
		switchLanesEarly = switchLanesEarly || false;


		var lineLane = switchLanesEarly ? secondCommit.lane : firstCommit.lane;

		// the connection has 3 segments:
		// - from the x/y center of firstCommit.dot to the rightmost end (x) of the commit's column, with y=lineLane
		// - from the rightmost end of firstCommit's column, to the leftmost end of secondCommit's column
		// - from the leftmost end of secondCommit's column (y=lineLane) to the x/y center of secondCommit



		// draw the line between the two dots
		paper.path( getSvgLineString( [firstCommit.dot.attr('cx'),
									   firstCommit.dot.attr('cy')],

									  [firstCommit.dot.attr('cx') + (cfg.columnWidth/2),
									   lineLane.centerY],

									[secondCommit.dot.attr('cx') - (cfg.columnWidth/2),
									   lineLane.centerY],

									  [secondCommit.dot.attr('cx'),
									   secondCommit.dot.attr('cy')]
			)).attr({
				stroke: lineLane.color, "stroke-width": 2
			}).toBack();
		return;
	}

	// set together a path string from any amount of arguments
	// each argument is an array of [x, y]
	function getSvgLineString( ) {
		if (arguments.length < 2) return

		// we are using a little trick here: Due to the right-to-left direction of the graph, the fix point is at the
		// right hand side. But the top-right point will change each time we extend the drawing area, which would
		// result in a terrible parsing and re-assembling every single sub path.
		// Instead, we use the moveto feature to start the line at "our" base (top-right), and draw the lines using
		// relative linetos: The linetos will always stay the same - we only have to update the base

		var svgString = 'M' + arguments[0][0] + ' ' + arguments[0][1];

		for (var i = 1, j = arguments.length; i < j; i++){

			// x =0 means a relatively unchanged x value

			svgString += 'L' + arguments[i][0] + ' ' + arguments[i][1];

		}

		return svgString;
	}

	function lineClickHandler() {
		console.log('Hi, I am connecting', this.data('theCommit'), 'with', this.data('theChild'));

		flashDot( this.data('theCommit').dot );
		flashDot( this.data('theChild').dot );
	}

	function flashDot( dot ) {
		var origCol = dot.attr('fill');
		dot.attr('fill', '#00FF00');
		dot.animate( {
			'fill': origCol
		}, 1000);
	}

	function dotClickHandler(evt) {
		console.log(this.data('commit'));
	}

	function getLaneInfo( laneNumber ) {
		return {
			'number': laneNumber,
			'centerY': ( laneNumber * cfg.laneHeight ) + (cfg.laneHeight/2),
			'color': cfg.laneColors[ laneNumber % cfg.laneColors.length ]
		};
	}

	function getXPositionForColumnNumber( columnNumber ) {
		// we want the column's center point
		return ( paper.width - ( columnNumber * cfg.columnWidth ) - (cfg.columnWidth / 2 ));
	}

	function initScrolling() {
		commitsGraph.on('mousedown', handleMouseDown);
		var lastX, lastY;

		function handleMouseDown( evt ) {
			commitsGraph.on('mousemove', handleMouseMove);
			commitsGraph.on('mouseup', handleMouseUp);
			commitsGraph.on('mouseleave', handleMouseUp);
			lastX = evt.pageX;
			lastY = evt.pageY;


		}

		function handleMouseMove(evt) {
			evt.preventDefault();

			commitsGraph[0].scrollLeft = commitsGraph[0].scrollLeft + lastX - evt.pageX;
			commitsGraph[0].scrollTop = commitsGraph[0].scrollTop + lastY - evt.pageY;

			lastX = evt.pageX;
			lastY = evt.pageY;
		}

		function handleMouseUp(evt) {
			commitsGraph.off('mousemove', handleMouseMove);
			commitsGraph.off('mouseup', handleMouseUp);
			commitsGraph.off('mouseleave', handleMouseUp);
		}
	}


	refreshButton.click(refreshButtonClickHandler);
	initScrolling();
	// load initial data
	fetchCommitData( nextPage );


});