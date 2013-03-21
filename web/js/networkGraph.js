/**
 * Network Graph JS
 * This File is a part of the GitList Project at http://gitlist.org
 *
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author Lukas Domnick <lukx@lukx.de> http://github.com/lukx
 */
$( function() {

	// initialise network graph only when there is one network graph container on the page
	if( $('table.network-graph').length !== 1 ) {
		return;
	}

	var
	commitsTable = $('table.network-graph').first(),
	commitsDirectory = {};

	var url = commitsTable.data('source');

	$.ajax({
		dataType: "json",
		url: url,
		success: handleNetworkDataLoaded,
		error: handleNetworkDataError
	});

	function handleNetworkDataLoaded( data ) {

		// no commits or empty commits array? Well, we can't draw a graph of that
		if( !data.commits || data.commits.length < 1 ) {
			handleNoAvailableData();
			return;
		};

		registerCommitsInDictionary( data.commits );
		prepareCommits( data.commits );
		renderCommits( data.commits );
	}

	function registerCommitsInDictionary( commits ) {
		$.each(commits, function( index, commit ) {
			commitsDirectory[ commit.hash ] = commit;
		});
	}

	function handleNetworkDataError( err ){
		console.log(err);
	}

	function handleNoAvailableData() {
		graphContainer.html('It seems as though there are no commits in this repository / branch...');
	}

	var xOffset = 10;
/*	function renderGraphPart( commit, row ) {
		if ( typeof commit === 'string' ) {
			commit = commits[commit];
			if( !commit) {
				// this commit is not part of the plot data
				console.log('found out-of bound commit', commit);
				return;
			}
		}

		row = row || 0;
		if( row > 0 ) console.log('row', row);
		if( !commit.hasOwnProperty('dot') ) {
			commit.dot = r.circle( xOffset, (row * 10) + 10 , 3 );
			commit.dot.attr({'fill': getColorForRow(row), 'stroke':'none'});
			commit.dot.data('commit', commit);
			commit.dot.click( dotClickHandler );

			if( commit.parentsHash.length > 0 ) {
				xOffset = xOffset + 20;
			}
			$.each(commit.parentsHash, function( index, value ) {
				// increase the Y offset

				renderGraphPart( value, row+index );
			});
		}
	}
	*/


	var parentsBeingWaitedFor = {};

	function prepareCommits( commits ) {
		$.each( commits, function ( index, commit) {
			prepareCommit( commit );
		});
	}

	var occupiedLanes = [];

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



		// after a fork, the occupied lanes must be cleaned up. The children used some lanes we no longer occupy
		if (commit.isFork === true ) {
			$.each( commit.children, function( key, thisChild ) {
				console.log('Freeing lane ', thisChild.lane.number);
				occupiedLanes[thisChild.lane.number] = false;
			});
		}
		// now the lane we chose must be marked occupied again.
		console.log('Occupying lane ', commit.lane.number)
		occupiedLanes[commit.lane.number] = true;

		// at this point, we know which lane we are on, and which lanes are occupied. store this info for
		// later when we are rendering the "thru"-lines, i.e. those lines connecting two commits which are not on this
		// commit's row
		commit.occupiedLanes = occupiedLanes.slice();

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

	function renderCommit( commit ) {

		var tableRow = $('<tr></tr>');

		var dotRadius = 3;
		var rowHeight = 42; //c'mon, make this diviseable by 2, thanks
		// the required canvas width is at least the lane center plus the radius - but that will be added later
		var requiredCanvasWidth = commit.lane.centerX;
		// now the parent with the highest lane number determines whether we need more space to the right...
		$.each( commit.parents, function(idx, thisParent) {
			requiredCanvasWidth = Math.max( requiredCanvasWidth, thisParent.lane.centerX );
		});
		$.each( commit.children, function(idx, thisChild) {
			requiredCanvasWidth = Math.max( requiredCanvasWidth, thisChild.lane.centerX );
		});

		requiredCanvasWidth = requiredCanvasWidth + dotRadius;

		// build the table row and insert it, so we can find out the required height
		var drawingArea = $('<td class="network-tree-segment"></td>');
		tableRow.append(drawingArea);
		tableRow.append('<td>' + commit.date.toLocaleString() +'</td>');
		tableRow.append('<td>' + commit.author.name + '</td>');
		tableRow.append('<td>' + commit.message + '</td>');
		tableRow.data('theCommit', commit);

		commitsTable.append(tableRow);

		// awesome, now we have the height!
		var paper = Raphael( drawingArea[0], requiredCanvasWidth, rowHeight);
		tableRow.data('rjsPaper', paper);

		commit.dot = paper.circle( commit.lane.centerX, rowHeight/2, dotRadius );
		commit.dot.attr({
			fill: commit.lane.color,
			stroke: 'none'
		});

		// render the line from this commit to it's children, but on their lane
		$.each( commit.children, function ( idx, thisChild ) {
			// first draw the joint for this commit's child
			console.log('BEFORE ERR', thisChild.lane);
			paper.path( getSvgLineString( commit.lane.centerX, rowHeight/2, thisChild.lane.centerX, 0)).attr({
				stroke: thisChild.lane.color, "stroke-width": 2
			}).toBack();

			// iterate up the commit table
			var nRow = tableRow.prev('tr');
			while( nRow.length > 0 ) {
				if ( nRow.data('theCommit') === thisChild ) {
					// we are done, render only the bottom half line
					nRow.data('rjsPaper')
						.path(
							getSvgLineString( thisChild.lane.centerX, rowHeight,
											  thisChild.lane.centerX, rowHeight/2) )
						.attr({
							stroke: thisChild.lane.color, "stroke-width": 2
						}).toBack();
					return;
				} else {
					nRow.data('rjsPaper')
						.path(
							getSvgLineString( thisChild.lane.centerX, rowHeight,
								thisChild.lane.centerX, 0) )
						.attr({
							stroke: thisChild.lane.color, "stroke-width": 2
						}).toBack();
					nRow = nRow.prev('tr');
				}
			}
		});
	}

	function getSvgLineString( fromX, fromY, toX, toY ) {
		return 'M' + fromX + ',' + fromY + 'L' + toX + ',' + toY;
	}

	function dotClickHandler(evt) {
		console.log(this.data('commit'));
	}




	function getLaneInfo( laneNumber ) {
		var laneColors = ['#ff0000', '#0000FF', '#00FFFF', '#00FF00', '#FFFF00', '#ff00ff'];

		return {
			'number': laneNumber,
			'centerX': ( laneNumber * 10 ) + 5,
			'color': laneColors[ laneNumber % laneColors.length ]
		}
	}


});