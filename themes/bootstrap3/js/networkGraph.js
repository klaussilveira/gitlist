/**
 * Network Graph JS
 * This File is a part of the GitList Project at http://gitlist.org
 *
 * @license http://www.opensource.org/licenses/bsd-license.php
 * @author Lukas Domnick <lukx@lukx.de> http://github.com/lukx
 */

( function( $ ){
	// global config
	var cfg = {
		laneColors: ['#ff0000', '#0000FF', '#00FFFF', '#00FF00', '#FFFF00', '#ff00ff'],
		laneHeight: 20,
		columnWidth: 42,
		dotRadius: 3
	};

	// Define the jQuery Plugins

	/**
	 * DragScrollr is a custom made x/y-Drag Scroll Plugin for Gitlist
	 *
	 * TODO: Make this touch-scrollable
	 */
	$.fn.dragScrollr = function() {
		var lastX,
			lastY,
			hotZone = 200,
			container = this.first(),
			domElement = container[0]; // so basically container without the jQuery stuff

		function handleMouseDown( evt ) {
			container.on('mousemove', handleMouseMove);
			container.on('mouseup', handleMouseUp);
			container.on('mouseleave', handleMouseUp);
			lastX = evt.pageX;
			lastY = evt.pageY;
		}

		function handleMouseMove(evt) {
			evt.preventDefault();

			// save the last scroll position to figure out whether the scroll event has entered the hot zone
			var lastScrollLeft = domElement.scrollLeft;
			domElement.scrollLeft = domElement.scrollLeft + lastX - evt.pageX;
			domElement.scrollTop = domElement.scrollTop + lastY - evt.pageY;

			if( lastScrollLeft > hotZone && domElement.scrollLeft <= hotZone ) {
				container.trigger('enterHotZone');
			}

			// when we move into the hot zone

			lastX = evt.pageX;
			lastY = evt.pageY;
		}

		function handleMouseUp(evt) {
			container.off('mousemove', handleMouseMove)
				.off('mouseup', handleMouseUp)
				.off('mouseleave', handleMouseUp);
		}

		// now bind the initial event
		container.on('mousedown', handleMouseDown);

		// return this instead of container, because of the .first() we applied - remember?
		return this;
	};

	function graphLaneManager() {
		var that = {},
			occupiedLanes = [];

		// "private" methods
		function findLaneNumberFor( commit ) {

			if( commit.lane ) {
				// oh? we've already got a lane?
				return commit.lane.number;
			}

			// find out which lane may draw our dot on. Start with a free one
			var laneNumber = findFreeLane();

			// if the child is a merge, we need to figure out which lane we may render this commit on.
			// Rules are simple: A "parent" by the same author as the merge may render on the same line as the child
			// others take the next free lane.
			// furthermore, commits in a linear line of events may stay on the same lane, too
			if( commit.children.length > 0) {
				if( !commit.children[0].isMerge // linear ...
					|| ( commit.children[0].isMerge	&& commit.children[0].author.email === commit.author.email ) // same author
					) {
					laneNumber = commit.children[0].lane.number;
				}
			}

			return laneNumber;
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

		that.occupy = function( lane ) {
			// make sure we work with lane numbers here
			if( typeof lane === 'object' ) {
				lane = lane.number;
			}

			occupiedLanes[lane] = true;
		};

		that.free = function( lane ) {
			// make sure we work with lane numbers here
			if( typeof lane === 'object' ) {
				lane = lane.number;
			}

			occupiedLanes[lane] = false;
		};

		that.getLaneForCommit = function( commit ) {
			// does this commit have a lane already?
			if( commit.lane ) return commit.lane;

			var laneNumber = findLaneNumberFor( commit );
			return that.getLane( laneNumber );
		};

		that.getLane = function(laneNumber) {
			return {
				'number': laneNumber,
				'centerY': ( laneNumber * cfg.laneHeight ) + (cfg.laneHeight/2),
				'color': cfg.laneColors[ laneNumber % cfg.laneColors.length ]
			};
		};

		return that;
	}

	function commitDetailOverlay( ) {
		var that = {},
			el = $('<div class="network-commit-overlay"></div>'),
			imageDisplay = $('<img/>').appendTo(el),
			messageDisplay = $('<h4></h4>').appendTo(el),
			metaDisplay = $('<p></p>').appendTo(el),
			authorDisplay = $('<a rel="author"></a>').appendTo(metaDisplay),
			dateDisplay = $('<span></span>').appendTo(metaDisplay),

			commit;

		el.hide();
		/**
		 * Pads an input number with one leading '0' if needed, and assure it's a string
		 *
		 * @param input Number
		 * @returns String
		 */
		function twoDigits( input ) {
			if( input < 10 ) {
				return '0' + input;
			}

			return '' + input;
		}

		/**
		 * Transform a JS Native Date Object to a string, maintaining the same format given in the commit_list view
		 * 'd/m/Y \\a\\t H:i:s'
		 *
		 * @param date Date
		 * @returns String
		 */
		function getDateString( date )  {
			return twoDigits( date.getDate() )	+ '/'
				+ twoDigits( date.getMonth() + 1 ) + '/'
				+ date.getFullYear() + ' at '
				+ twoDigits(date.getHours()) + ':'
				+ twoDigits(date.getMinutes()) + ':'
				+ twoDigits(date.getSeconds());
		}

		/**
		 * update the author view
		 *
		 * @param author
		 */
		function setAuthor( author ) {
			authorDisplay.html(author.name)
				.attr('href', 'mailto:' + author.email );

			imageDisplay.attr('src', author.image );
		}

		/**
		 * Set the commit that is being displayed in this detail overlay instance
		 *
		 * @param commit
		 * @return that
		 */
		that.setCommit = function( commit ) {
			setAuthor( commit.author );
			dateDisplay.html( ' authored on ' + getDateString( commit.date ) );
			messageDisplay.html( commit.message );
			return that;
		};

		// expose some jquery functions

		that.show = function() {
			el.show();
			return that;
		};

		that.hide = function() {
			el.hide();
			return that;
		};

		that.appendTo = function(where) {
			el.appendTo(where);

			return that;
		};

		that.positionTo = function( x, y ) {
			el.css('left', x + 'px');
			el.css('top', y + 'px');
		};

		that.outerWidth = function( ) {
			return el.outerWidth.apply(el, arguments);
		};


		return that;
	}

	function commitDataRetriever( startPage, callback ) {
		var that = {},
			nextPage = startPage,
			isLoading = false,
			indicatorElements;

		that.updateIndicators = function() {
			if( isLoading ) {
				$(indicatorElements).addClass('loading-commits');
			} else {
				$(indicatorElements).removeClass('loading-commits');
			}
		};

		that.bindIndicator = function( el ) {
			if( !indicatorElements ) {
				indicatorElements = $(el);
			} else {
				indicatorElements = indicatorElements.add(el);
			}

		};

		that.unbindIndicator = function( el ) {
			indicatorElements.not( el );
		};

		function handleNetworkDataLoaded(data) {
			isLoading = false;
			that.updateIndicators();
			nextPage = data.nextPage;

			if( !data.commits || data.commits.length === 0 ) {
				callback( null );
			}

			callback(data.commits);
		}

		function handleNetworkDataError() {
			throw "Network Data Error while retrieving Commits";
		}

		that.retrieve = function() {

			if( !nextPage ) {
				callback( null );
				return;
			}

			isLoading = true;
			that.updateIndicators();
			$.ajax({
				dataType: "json",
				url: nextPage,
				success: handleNetworkDataLoaded,
				error: handleNetworkDataError
			});
		};

		that.hasMore = function () {
			return ( !!nextPage );
		};

		return that;
	}


	// the $(document).ready starting point
	$( function() {

		// initialise network graph only when there is one network graph container on the page
		if( $('div.network-graph').length !== 1 ) {
			return;
		}

		var
		// the element into which we will render our graph
			commitsGraph = $('div.network-graph').first(),
			laneManager = graphLaneManager(),
			dataRetriever = commitDataRetriever( commitsGraph.data('source'), handleCommitsRetrieved  ),
			paper = Raphael( commitsGraph[0], commitsGraph.width(), commitsGraph.height()),
			usedColumns = 0,
			detailOverlay = commitDetailOverlay();

		dataRetriever.bindIndicator( commitsGraph.parent('.network-view') );
		detailOverlay.appendTo( commitsGraph );


		function handleEnterHotZone() {
			dataRetriever.retrieve();
		}

		function handleCommitsRetrieved( commits ) {

			// no commits or empty commits array? Well, we can't draw a graph of that
			if( commits === null ) {
				handleNoAvailableData();
				return;
			}

			prepareCommits( commits );
			renderCommits( commits );
		}

		function handleNoAvailableData() {
			window.console && console.log('No (more) Data available');
		}

		var awaitedParents = {};

		function prepareCommits( commits ) {
			$.each( commits, function ( index, commit) {
				prepareCommit( commit );
			});
		}

		function prepareCommit( commit ) {
			// make "date" an actual JS Date object
			commit.date = new Date(commit.date*1000);

			// the parents will be filled once they have become prepared
			commit.parents = [];

			// we will want to store this commit's children
			commit.children = getChildrenFor( commit );

			commit.isFork  = ( commit.children.length > 1 );
			commit.isMerge = ( commit.parentsHash.length > 1 );

			// after a fork, the occupied lanes must be cleaned up. The children used some lanes we no longer occupy
			if ( commit.isFork === true ) {
				$.each( commit.children, function( key, thisChild ) {
					// free this lane
					laneManager.occupy( thisChild.lane );
				});
			}

			commit.lane = laneManager.getLaneForCommit( commit );

			// now the lane we chose must be marked occupied again.
			laneManager.occupy( commit.lane );

			registerAwaitedParentsFor( commit );
		}

		/**
		 * Add a new childCommit to the dictionary of awaited parents
		 *
		 * @param commit who is waiting?
		 */
		function registerAwaitedParentsFor( commit ) {
			// This commit's parents are not yet known in our little world, as we are rendering following the time line.
			// Therefore we are registering this commit as "waiting" for each of the parent hashes
			$.each( commit.parentsHash, function( key, thisParentHash ) {
				// If awaitedParents does not already have a key for thisParent's hash, initialise as array
				if( !awaitedParents.hasOwnProperty(thisParentHash) ) {
					awaitedParents[thisParentHash] = [ commit ];
				} else {
					awaitedParents[ thisParentHash ].push( commit );
				}
			});
		}

		function getChildrenFor( commit ) {
			var children = [];

			if( awaitedParents.hasOwnProperty( commit.hash )) {
				// there are child commits waiting
				children = awaitedParents[ commit.hash ];

				// let the children know their parent objects
				$.each( children, function(key, thisChild ) {
					thisChild.parents.push( commit );
				});

				// remove this item from parentsBeingWaitedFor
				delete awaitedParents[ commit.hash ];
			}

			return children;
		}

		var lastRenderedDate = new Date(0);
		function renderCommits( commits ) {

			var neededWidth = ((usedColumns + Object.keys(commits).length) * cfg.columnWidth);

			if (  neededWidth > paper.width ) {
				extendPaper( neededWidth, paper.height  );
			} else if( dataRetriever.hasMore() ) {
				// this is the case when we have not loaded enough commits to fill the paper yet. Get some more then...
				dataRetriever.retrieve();
			}

			$.each( commits, function ( index, commit) {
				if( lastRenderedDate.getYear() !== commit.date.getYear()
					|| lastRenderedDate.getMonth() !== commit.date.getMonth()
					|| lastRenderedDate.getDate() !== commit.date.getDate() ) {
					// TODO: If desired, one could add a time scale on top, maybe.
				}
				renderCommit(commit);
			});
		}

		function renderCommit( commit ) {
			// find the column this dot is drawn on
			usedColumns++;
			commit.column = usedColumns;

			commit.dot = paper.circle( getXPositionForColumnNumber(commit.column), commit.lane.centerY, cfg.dotRadius );
			commit.dot.attr({
					fill: commit.lane.color,
					stroke: 'none',
					cursor: 'pointer'
				})
				.data('commit', commit)
				.mouseover( handleCommitMouseover )
				.mouseout( handleCommitMouseout )
				.click( handleCommitClick );

			// maybe we have not enough space for the lane yet
			if( commit.lane.centerY + cfg.laneHeight > paper.height ) {
				extendPaper( paper.width, commit.lane.centerY + cfg.laneHeight )
			}

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
			// default value for switchLanesEarly
			switchLanesEarly = switchLanesEarly || false;

			var lineLane = switchLanesEarly ? secondCommit.lane : firstCommit.lane;

			// the connection has 4 stops, resulting in the following 3 segments:
			// - from the x/y center of firstCommit.dot to the rightmost end (x) of the commit's column, with y=lineLane
			// - from the rightmost end of firstCommit's column, to the leftmost end of secondCommit's column
			// - from the leftmost end of secondCommit's column (y=lineLane) to the x/y center of secondCommit

			paper.path(
				getSvgLineString(
					[firstCommit.dot.attr('cx'), 						firstCommit.dot.attr('cy')],
					[firstCommit.dot.attr('cx') + (cfg.columnWidth/2),	lineLane.centerY],
					[secondCommit.dot.attr('cx') - (cfg.columnWidth/2),	lineLane.centerY],
					[secondCommit.dot.attr('cx'),						secondCommit.dot.attr('cy')]
				)
			).attr({ "stroke": lineLane.color, "stroke-width": 2 }).toBack();
		}

		// set together a path string from any amount of arguments
		// each argument is an array of [x, y] within the paper's coordinate system
		function getSvgLineString( ) {
			if (arguments.length < 2) return;

			var svgString = 'M' + arguments[0][0] + ' ' + arguments[0][1];

			for (var i = 1, j = arguments.length; i < j; i++){
				svgString += 'L' + arguments[i][0] + ' ' + arguments[i][1];
			}

			return svgString;
		}

		function handleCommitMouseover(evt) {
			detailOverlay.setCommit( this.data('commit'))
				.show();

			var xPos = evt.pageX - commitsGraph.offset().left + commitsGraph.scrollLeft() - (detailOverlay.outerWidth()/2);
			// check that x doesn't run out the viewport
			xPos = Math.max( xPos, commitsGraph.scrollLeft() + 10);
			xPos = Math.min( xPos, commitsGraph.scrollLeft() + commitsGraph.width() - detailOverlay.outerWidth() - 10);

			detailOverlay.positionTo( xPos,
							 evt.pageY - commitsGraph.offset().top + commitsGraph.scrollTop() + 10);
		}

		function handleCommitMouseout(evt) {
			detailOverlay.hide();
		}

		function handleCommitClick( evt ) {
			window.open( this.data('commit').details );
		}

		function getXPositionForColumnNumber( columnNumber ) {
			// we want the column's center point
			return ( paper.width - ( columnNumber * cfg.columnWidth ) + (cfg.columnWidth / 2 ));
		}

		function extendPaper( newWidth, newHeight ) {
			var deltaX = newWidth - paper.width;

			paper.setSize( newWidth, newHeight );
			// fixup parent's scroll position
			paper.canvas.parentNode.scrollLeft = paper.canvas.parentNode.scrollLeft + deltaX;

			// now fixup the x positions of existing circles and lines
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

		commitsGraph.dragScrollr();
		commitsGraph.on('enterHotZone', handleEnterHotZone);
		// load initial data
		dataRetriever.retrieve( );
	});
}( jQuery ));
