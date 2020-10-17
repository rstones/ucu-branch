
// get the currently logged in user
// find the depts/sites they are reps for
//
// get all the members visible for this user
//
// get all the group info for the visible members and link to the members
//
// display in table
//
// create email list copy button for each rep group of logged in user
// build these lists while looping over members initially
//
// ensure primary email is the one displayed


(function($) {
 
    // hard coded the sites here >_<
    var allSites = ['Waterloo', 'Shrivenham', 'Strand', 'Denmark Hill', 'Guy\'s', 'Virginia Woolf Building', 'St Thomas\'', 'Bush House']
     
    // some 'global' vars to be populated by the async api calls
    var currentlyLoggedInMember = null;
    var members = null;

    // get the currently logged in user details
    var getCurrentlyLoggedInMember = function() {
        // there isn't a CRM api endpoint to get the currently logged in user
        // so I got it in php and hid it in the page template
        let id = $('#logged-in-member-id').text();
        if (id.length) {
            CRM.api4('Contact', 'get', {
                select: ["first_name", "last_name", "group_contacts.group_id"],
                where: [["id", "=", id]]
            }).then(function(contacts) {
                // get the rep groups this user is a member of
                let member = contacts[0];
                let groupIDs = [];
                for (let j = 0; j < member['group_contacts'].length; j++) {
                    groupIDs.push(member['group_contacts'][j]['group_id']);
                }

                CRM.api4('Group', 'get', {
                    select: ["id", "name"],
                    where: [["id", "IN", groupIDs], ["name", "LIKE", "%Reps"]]
                }).then(function(groups) {
                    member['repGroups'] = groups;
                    currentlyLoggedInMember = member;
                });

            });
        }
    }

    // get the member data
    // CiviCRM will handle the access control lists so the currently logged in user
    // will only see the members they are allowed to
    var getMembers = function() {
        
        CRM.api4('Contact', 'get', {
            select: ["id", "external_identifier", "first_name", "last_name", "UCU_Branch.Contract_Type", "emails.email", "UCU_Branch.Employment_Function", "UCU_Branch.Join_Date", "group_contacts.group_id"],
            where: [["contact_type", "=", "Individual"]]
        }).then(function(contacts) {
            
            var memberData = contacts;
            var groupIDs = [];

            // loop over member data to construct fields for display
            // and to get the group ids
            for (let i = 0; i < memberData.length; i++) {
                let member = memberData[i];
                // join names
                member['full_name'] = member['first_name'] + ' ' + member['last_name'];

                // get primary email. is it last in the list?
                let emails = member['emails'];
                let email = '';
                if (emails.length) {
                    email = emails[emails.length - 1]['email'];
                }
                member['primary_email'] = email;

                // slice join date
                if (member['UCU_Branch.Join_Date']) {
                    member['UCU_Branch.Join_Date'] = member['UCU_Branch.Join_Date'].slice(0, 10);
                } else {
                    member['UCU_Branch.Join_Date'] = '[Missing info]';
                }

                // get groups ids
                for (let j = 0; j < member['group_contacts'].length; j++) {
                    groupID = member['group_contacts'][j]['group_id'];
                    if (!groupIDs.includes(groupID)) {
                        groupIDs.push(groupID);
                    }
                }
            }

            // now get the group names from group_contacts.group_id
            // and assign each member a site and department
            CRM.api4('Group', 'get', {
                select: ["id", "name"],
                where: [["id", "IN", groupIDs], ["name", "LIKE", "%Members"]]
            }).then(function(groups) {
                // put groups into an object for easy reference
                let groupObj = {};
                for (let k = 0; k < groups.length; k++) {
                    let group = groups[k];
                    groupObj[group['id']] = group['name'];
                }
                
                // loop through members again and assign them site and
                // department based the group_contacts.group_id fields
                for (let i = 0; i < memberData.length; i++) {
                    let member = memberData[i];
                    let memberGroups = member['group_contacts'];

                    for (let j = 0; j < memberGroups.length; j++) {
                        let groupID = memberGroups[j]['group_id'];
                        let name = groupObj[groupID];
                        // if groupID isn't in returned groups its a reps group
                        if (name) {                            
                            name = name.split(' ').slice(0, -1).join(' ');
                        
                            if (allSites.includes(name)) {
                                if (member['site']) {
                                    member['site'] += ' / ' + name
                                } else {
                                    member['site'] = name;
                                }

                            } else {

                                if (member['department']) {
                                    member['department'] += ' / ' + name;
                                } else {
                                    member['department'] = name;
                                }
                            }
                        }
                    }

                    if (!member['site']) {
                        member['site'] = '[Missing info]';
                    }
                    if (!member['department']) {
                        member['department'] = '[Missing info]';
                    }
                }

                // set the 'global' var now members data is fetched and processed
                members = memberData;        

            }, function(failure) {
                // handle failure
            });

        }, function(failure) {
          // handle failure
        });

    }

    // function to construct the members table
    // takes list memberData is argument
    var insertTable = function(memberData) {

        // define columns to be displayed in table
        var memberColumns = {
            'external_identifier': 'Membership No.',
            'full_name': 'Full Name',
            'primary_email': 'Email',
            'UCU_Branch.Contract_Type': 'Contract Type',
            'UCU_Branch.Employment_Function': 'Employment Function',
            'site': 'Site',
            'department': 'Department',
            'UCU_Branch.Join_Date': 'Join Date'
        }

        // build the table
        var target = $('#view-members-table');
        if (target.length) {
            var table = target.tableSortable({
                data: memberData,
                columns: memberColumns,
                rowsPerPage: 20,
                pagination: true,
                searchField: '#table-search',
                tableDidUpdate: function() {
                    //console.log(this.getData());
                }
            });
        }

    }


    var insertEmailListButtons = function(currentMember, memberData) {
        let repGroups = currentMember.repGroups;
        
        if (repGroups.length === 0) {
            // this is an admin don't need email list button
            return;
        } else {
            for (let i = 0; i< repGroups.length; i++) {
                let name = repGroups[i].name.split(' ').slice(0, -1).join(' ');
                let emailList = '';
                for (let j = 0; j < memberData.length; j++) {
                    let member = memberData[j];
                    if ((member['site'] && member['site'].indexOf(name) !== -1)
                        || (member['department'] && member['department'].indexOf(name) !== -1)) {
                        emailList += member['primary_email'] + '; ';
                    }
                }
                let buttonID = 'email-list-btn-' + i;
                $('#email-list-btns').append('<div id="'+ buttonID + '" class="email-list-btn" data-clipboard-text=""><i class="fa fa-clipboard"></i> Copy ' + name + ' emails to clipboard</div>');
                $('#' + buttonID).click(function(event) {
                    $(this).fadeOut(75).fadeIn(75).fadeOut(75).fadeIn(75).fadeOut(75).fadeIn(75);
                });
                new ClipboardJS('#' + buttonID);
                $('#' + buttonID).attr('data-clipboard-text', emailList);
            }
        }
    }

    $(document).ready(function() {

        // fetch data asynchronously using CRM api4
        getCurrentlyLoggedInMember();
        getMembers();

        var checkApiCallReturned = function() {
            if (!(currentlyLoggedInMember && members)) {
                return;
            } else {
                clearTimeout(intervalID);
                insertEmailListButtons(currentlyLoggedInMember, members);
                insertTable(members);
            }
        }

        var intervalID = setInterval(checkApiCallReturned, 200);
        
    });
    
})(jQuery);
