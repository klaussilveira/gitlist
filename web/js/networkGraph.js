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

	cfg = {
		laneColors: ['#ff0000', '#0000FF', '#00FFFF', '#00FF00', '#FFFF00', '#ff00ff'],
		laneWidth: 10,
		dotRadius: 3,
		rowHeight: 42 // thanks for making this divisable by two
	},

	// the table element into which we will render our graph
	commitsTable = $('table.network-graph').first(),
	url = commitsTable.data('source');

	function fetchCommitData( url ) {

		$.ajax({
			dataType: "json",
			url: url,
			success: handleNetworkDataLoaded,
			error: handleNetworkDataError
		});
	}

	// load initial data
	fetchCommitData( url );

	//only for debug purposes!!
	var nextPage;
	window.fcd = function () {
		fetchCommitData(nextPage);
	};

	function handleNetworkDataLoaded( data ) {
		console.log('Retreived Commit Data', data);

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
			console.log('taking commit out', commit.hash, parentsBeingWaitedFor);
		}

		commit.isFork  = ( commit.children.length > 1 );
		commit.isMerge = ( commit.parentsHash.length > 1 );



		// after a fork, the occupied lanes must be cleaned up. The children used some lanes we no longer occupy
		if (commit.isFork === true ) {
			$.each( commit.children, function( key, thisChild ) {
				console.log('Freeing lane ', thisChild.lane.number);
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
		console.log('Occupying lane ', commit.lane.number);
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

		// the required canvas width is at least the lane center plus the radius - but that will be added later

		// now the parent with the highest lane number determines whether we need more space to the right...


		// build the table row and insert it, so we can find out the required height
		var drawingArea = $('<div class="network-tree-segment"></div>');
		tableRow.append( $('<td/>').append(drawingArea));
		tableRow.append('<td>' + commit.date.toLocaleString() +'</td>');
		tableRow.append('<td>' + commit.author.name + '</td>');
		tableRow.append('<td>' + commit.message + '</td>');
		tableRow.data('theCommit', commit);

		commitsTable.append(tableRow);

		// awesome, now we have the height!
		var paper = Raphael( drawingArea[0], cfg.laneWidth * maxLanes, cfg.rowHeight);
		tableRow.data('rjsPaper', paper);

		commit.dot = paper.circle( commit.lane.centerX, cfg.rowHeight/2, cfg.dotRadius );
		commit.dot.attr({
			fill: commit.lane.color,
			stroke: 'none'
		});

		// render the line from this commit to it's children, but on their lane
		$.each( commit.children, function ( idx, thisChild ) {

			// for each child,
			// move upwards in <tr>s, beginning from the "commit" (not: child!)
			// until the tr.data('theCommit') is thisChild.
			//
			// if there is one child only, stay on the commit's lane as long as possible,
			// but if there is more than one child, switch to the child's lane ASAP.
			// this is to display merges and forks where they happen (ie. at a commit node/ a dot), rather than
			// "forking" from a line

			var nRow = tableRow.prev('tr'),
				// lineX holds the X position the line will be on while it's straight
				lineLane = commit.lane;

			if( commit.isFork ) {
				lineLane = thisChild.lane;
			}

			// before iterating upwards as described above, the line part from the commit to the adequate lane
			// must be drawn
			tableRow.data('rjsPaper').path(
					getSvgLineString( commit.lane.centerX, cfg.rowHeight/2,
						lineLane.centerX, 0) )
				.attr({
					stroke: lineLane.color, "stroke-width": 2
				})
				.data('theCommit', commit).data('theChild', thisChild).click(lineClickHandler)
				.toBack();

			while( nRow.length > 0 ) {

				if ( nRow.data('theCommit') === thisChild ) {
					// we are done, render only the bottom half line towards the child
					// Starting at lineX, but moving to thisChild's lane.
					nRow.data('rjsPaper')
						.path(
							getSvgLineString( lineLane.centerX, cfg.rowHeight,
											  thisChild.lane.centerX, cfg.rowHeight/2) )
						.attr({
							stroke: lineLane.color, "stroke-width": 2
						})
						.data('theCommit', commit).data('theChild', thisChild).click(lineClickHandler)
						.toBack();
					return;
				} else {
					// this is just a common "throughput" line part from bottom of the TR to top without any X movement
					//
					// maybe the paper isn't big enough yet, so expand it first...
					nRow.data('rjsPaper')
						.path(
							getSvgLineString( lineLane.centerX, 0,
											  lineLane.centerX, cfg.rowHeight) )
						.attr({
							stroke: lineLane.color, "stroke-width": 2
						})
						.data('theCommit', commit).data('theChild', thisChild).click(lineClickHandler)
						.toBack();
					nRow = nRow.prev('tr');
				}
			}
		});
	}

	function getSvgLineString( fromX, fromY, toX, toY ) {
		return 'M' + fromX + ',' + fromY + 'L' + toX + ',' + toY;
	}

	function lineClickHandler(evt) {
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
			'centerX': ( laneNumber * cfg.laneWidth ) + (cfg.laneWidth/2),
			'color': cfg.laneColors[ laneNumber % cfg.laneColors.length ]
		}
	}


});