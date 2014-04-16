$(function() {
	if ($('#votingsDashboard').length > 0) {
		initializeVotings();
		bindNavigation();
	}

	$('a#goToGegenvorschlag').click(function() {
		var jumpToId = $(this).closest('div.abstimmungsvorlage').next().attr('id');
		scrollToElement('#' + jumpToId);
	});
	$('a#goToHauptvorlage').click(function() {
		var jumpToId = $(this).closest('div.abstimmungsvorlage').prev().attr('id');
		scrollToElement('#' + jumpToId);
	});

	$('.toggle-trigger').click(function() {
		toggleBlock(this);
	});

	$('.searchResults .abstimmungsvorlage-header').click(function(e) {
		$('.abstimmungsvorlage-content', $(this).parent()).slideToggle('slow', function() {
			$('i', $(this).parent()).toggleClass('icon-chevron-down');
		});
	})

});

function initializeVotings() {
    if (typeof metaVotingProposalId != 'undefined') {
        openVoting(metaVotingProposalId);

    } else {
        var firstVoting = $('.meta-abstimmungsvorlage').first().attr('id').split('-')[2];
        openVoting(firstVoting);
    }
}

function bindNavigation() {
    $('.meta-abstimmungsvorlage-navi').click(function() {
        var metaVotingId = $(this).attr('id').split('-')[3];
        $('.meta-abstimmungsvorlage-navi').unbind('click');
        openVoting(metaVotingId);
    });
}

function openVoting(metaVotingId) {

    $('#meta-abstimmungsvorlage-navi-' + metaVotingId).slideUp(300, function() {
        $('.meta-abstimmungsvorlage').not('#meta-abstimmungsvorlage-' + metaVotingId).slideUp(300, function() {
            $('#meta-abstimmungsvorlage-' + metaVotingId).slideDown(300, function() {
                $('.meta-abstimmungsvorlage-navi').not('#meta-abstimmungsvorlage-navi-' + metaVotingId).slideDown(300, function() {
                    $('#votingsDashboard').show();
                });
            });
        });
    });
    bindNavigation();

}

function scrollToElement(elementId){
    $('html, body').animate({
        scrollTop: $(elementId).offset().top
    }, 1000);
}

function toggleBlock(trigger) {
    $('.toggle-handle', trigger).toggleClass('toggle-handle-plus').promise().done(function() {
        $(trigger).next().slideToggle('fast').toggleClass('toggle-closed');
    })
}

function resetForm() {
    $("#searchForm").find("input[type=text], textarea, select").val("");
    $("#searchForm").find("input[type=checkbox]").attr('checked', false);
}

/* Modals */
function bindModals() {
	$('.hasModal').click(function(e) {
		e.preventDefault();
		var requestedLink = $(e.target).attr('href');
		var $modalContentContainer = $(this).next('div');
		var modalContent = $modalContentContainer.html();
		$(this).qtip({
			content: {
				text: $modalContentContainer
			},
			hide: false,
			show: {
				ready: true,
				modal: {
					on: true,
					blur: false
				}
			},
			position: {
				my: 'center', at: 'center',
				target: $(window)
			},
			style: {
				classes: 'qtip-easyvote qtip-rounded qtip-modal'
			},
			events: {
				render: function(event, api) {
					event.preventDefault();
					$('button.button-confirm', api.elements.content).click(function(e) {
						window.location = requestedLink;
					});
					$('a.qtip-close', api.elements.content).click(function(e) {
						api.hide(e);
					});
					$('button.button-cancel', api.elements.content).click(function(e) {
						api.hide(e);
					});
				},
				hide: function(event, api) {
					$(e.target).after('<div class="hidden">' + modalContent + '</div>');
					api.destroy();
				}
			}
		});
	});
}

/* Flashing tooltips */
function bindToolTips() {
	$('.hasTooltip').each(function() {
		$(this).qtip({
			content: {
				text: $(this).next('div')
			},
			hide: {
				delay: 200,
				fixed: true
			},
			show: {
				solo: true
			},
			style: {
				classes: 'qtip-easyvote qtip-rounded qtip-shadow qtip-easyvote-narrow'
			}
		});
	});
}

/* Display a passed flash message */
function displayFlashMessage(message) {
	var $flashMessageContainer = $('#flashMessageContainer');
	var $contentWrap = $('#contentWrap');
	$flashMessageContainer.html('<a class="pull-right qtip-close" aria-label="schliessen"><i class="icon icon-remove"></i></a>' + message);

	$contentWrap.qtip({
		show: '',
		hide: {
			fixed: true,
			inactive: 1000,
			delay: 2000
		},
		content: {
			prerender: true, // important
			text: $flashMessageContainer.html()
		},
		style: {
			classes: 'qtip-easyvote qtip-rounded qtip-modal qtip-shadow'
		},
		position: {
			my: 'center', at: 'center',
			target: $(window)
		},
		events: {
			render: function(event, api) {
				$('a.qtip-close', api.elements.content).click(function(e) {
					api.hide(e);
				});
			}
		}
	}).qtip('show');
}

/* Load poll result */
function loadPollResult(votingProposal) {
	var ajaxPollUri = ajaxUri + '&tx_easyvote_communityajax[action]=showPollForVotingProposal&tx_easyvote_communityajax[votingProposal]=' + votingProposal;
	$.ajax({
		url: ajaxPollUri,
		success: function(data) {
			$('.vote-result-up', '#abstimmungsvorlage-' + votingProposal).text(data['results'][0] + '%');
			$('.vote-result-down', '#abstimmungsvorlage-' + votingProposal).text(data['results'][1] + '%');
			$voteUpHandle = $('#voteUp-' + votingProposal);
			$voteDownHandle = $('#voteDown-' + votingProposal);

			$voteUpHandle.after('<div class="hidden">' + data['voteUpText'] + '</div>');
			$voteDownHandle.after('<div class="hidden">' + data['voteDownText'] + '</div>');
			bindToolTips();
			if (data['voteValue'] > 0) {
				if (data['voteValue'] === 1) {
					$voteUpHandle.addClass('vote-active');
					$voteDownHandle.addClass('vote-disabled');
				} else {
					$voteDownHandle.addClass('vote-active');
					$voteUpHandle.addClass('vote-disabled');
				}
			} else {
				$voteUpHandle.addClass('vote-up').removeClass('vote-active').removeClass('vote-disabled');
				$voteDownHandle.addClass('vote-down').removeClass('vote-active').removeClass('vote-disabled');
			}
		}
	});
}

var $body = $('body');

/* Load poll results for all votingProposals on loading the site */
$(function() {
	$('.abstimmungsvorlage').each(function() {
		var votingProposalUid = $(this).attr('id').split('-')[1];
		loadPollResult(votingProposalUid);
	});

	/* Undo vote */
	$body.on('click', '.vote-active', function() {
		var $trigger = $(this);
		$trigger.removeClass('vote-active');
		var votingProposalUid = $trigger.closest('.abstimmungsvorlage').attr('id').split('-')[1];
		var ajaxCallUri = ajaxUri + '&tx_easyvote_communityajax[action]=undoUserVoteForVotingProposal&tx_easyvote_communityajax[votingProposal]=' + votingProposalUid;
		$.ajax({
			url: ajaxCallUri,
			success: function(data) {
				loadPollResult(votingProposalUid);
			}
		});
	});

	/* upVote */
	$body.on('click', '.vote-up', function() {
		var $trigger = $(this);
		$trigger.removeClass('vote-up');
		var votingProposalUid = $trigger.closest('.abstimmungsvorlage').attr('id').split('-')[1];
		var ajaxCallUri = ajaxUri + '&tx_easyvote_communityajax[action]=voteForVotingProposal&tx_easyvote_communityajax[value]=1&tx_easyvote_communityajax[votingProposal]=' + votingProposalUid;
		$.ajax({
			url: ajaxCallUri,
			success: function(data) {
				displayFlashMessage(data['successText']);
				loadPollResult(votingProposalUid);
			}
		});
	});

	/* downVote */
	$body.on('click', '.vote-down', function() {
		var $trigger = $(this);
		$trigger.removeClass('vote-down');
		var votingProposalUid = $trigger.closest('.abstimmungsvorlage').attr('id').split('-')[1];
		var ajaxCallUri = ajaxUri + '&tx_easyvote_communityajax[action]=voteForVotingProposal&tx_easyvote_communityajax[value]=2&tx_easyvote_communityajax[votingProposal]=' + votingProposalUid;
		$.ajax({
			url: ajaxCallUri,
			success: function(data) {
				displayFlashMessage(data['successText']);
				loadPollResult(votingProposalUid);
			}
		});
	});
});

/* Load mobilized community users */
$(function() {
	if ($('#mobilizedCommunityUsers').length > 0) {
		newMobilizedCommunityUser();
		loadMobilizedCommunityUsers();
	}
});

function loadMobilizedCommunityUsers() {
	var ajaxDataUri = ajaxUri + '&tx_easyvote_communityajax[controller]=CommunityUser&tx_easyvote_communityajax[action]=listMobilizedCommunityUsers';
	$.ajax({
		url: ajaxDataUri,
		success: function(data) {
			$('#mobilizedCommunityUsers').html(data);
		}
	});
}

$(function() {
	/* Add new mobilized community user */
	$body.on('click', '#newMobilizedCommunityUser', function() {
		newMobilizedCommunityUser();
	});
});

function newMobilizedCommunityUser() {
	var ajaxDataUri = ajaxUri + '&tx_easyvote_communityajax[controller]=CommunityUser&tx_easyvote_communityajax[action]=newMobilizedCommunityUser';
	$.ajax({
		url: ajaxDataUri,
		success: function(data) {
			$('#newMobilizedCommunityUserPlaceholder').append(data);
		}
	});
}

$(function() {
	$body.on('submit', '.newMobilizedCommunityUser', function(e) {
		e.preventDefault();
		$(this).closest('div').remove();
		var $formData = $(this).serializeArray();
		var ajaxDataUri = ajaxUri + '&tx_easyvote_communityajax[controller]=CommunityUser&tx_easyvote_communityajax[action]=createMobilizedCommunityUser';
		$.ajax({
			url: ajaxDataUri,
			data: $formData,
			success: function(returnValue) {
				displayFlashMessage(returnValue);
				loadMobilizedCommunityUsers();
				newMobilizedCommunityUser();
			}
		});
	});

	/* Remove a mobilized community user */
	$body.on('submit', '.removeMobilizedCommunityUser', function(e) {
		e.preventDefault();
		var $formData = $(this).serializeArray();
		var ajaxDataUri = ajaxUri + '&tx_easyvote_communityajax[controller]=CommunityUser&tx_easyvote_communityajax[action]=removeMobilizedCommunityUser';
		$.ajax({
			url: ajaxDataUri,
			data: $formData,
			success: function(returnValue) {
				displayFlashMessage(returnValue);
				loadMobilizedCommunityUsers();
			}
		});
	});

	bindToolTips();
	bindModals();
});