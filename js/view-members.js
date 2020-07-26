
(function($) {
    $(document).ready(function() {

        // function construct the table and stuff
        var buildTable = function(memberData, memberObj) {
            // define columns to be displayed in table
            var memberColumns = {
                'external_identifier': 'Membership No.',
                'full_name': 'Full Name',
                'primary_email': 'Email',
                'UCU_Branch.Contract_Type': 'Contract Type',
                'UCU_Branch.Employment_Function': 'Empoyment Function',
                'site': 'Site',
                'department': 'Department'
            }

            // build the table
            var target = $('#view-members-target');
            if (target.length) {
                console.log('making table...');
                var table = target.tableSortable({
                    data: memberData,
                    columns: memberColumns,
                    rowsPerPage: 10,
                    pagination: true,
                    searchField: '#table-search',
                    formatCell: function(row, key) {
                        if (key == 'full_name') {
                            let civiID = memberObj[row['external_identifier']]['id'];
                            let url = '/wp-admin/admin.php?page=CiviCRM&q=civicrm/contact/view&reset=1&cid=' + civiID;
                            return '<a href="'+url+'">'+row[key]+'</a>';
                        }
                        return row[key];
                    },
                    tableDidUpdate: function() {
                        //console.log(this.getData());
                    }
                });
            }

        }


        // define clipboard object attached to email-list-copy-btn
        new ClipboardJS('#email-list-copy-btn');

        // animate email list copy btn for feedback
        $('#email-list-copy-btn').click(function(event) {
            $(this).fadeOut(75).fadeIn(75).fadeOut(75).fadeIn(75).fadeOut(75).fadeIn(75);
        });


        var sites = ['Waterloo', 'Shrivenham', 'Strand', 'Denmark Hill', 'Guy\'s', 'Virginia Woolf Building', 'St Thomas\'', 'Bush House']

        // get the member data
        CRM.api4('Contact', 'get', {
            select: ["id", "external_identifier", "first_name", "last_name", "UCU_Branch.Contract_Type", "emails.email", "UCU_Branch.Employment_Function", "group_contacts.group_id"],
            where: [["contact_type", "=", "Individual"]]
        }).then(function(contacts) {
            
            var memberData = contacts;
            // reorganise member data in object for easy access by property
            var memberObj = {};
            // construct email string to be copied to clipboard
            var emailString = '';
            // save group ids to get group names
            var groupIDs = [];

            // process member data for display and easy search later
            for (let i = 0; i < memberData.length; i++) {
                let member = memberData[i];
                // join names
                member['full_name'] = member['first_name'] + ' ' + member['last_name'];
                let emails = member['emails'];
                let email = '';
                if (emails.length) {
                    email = emails[emails.length - 1]['email'];
                }
                //let email = member['emails'][0]['email'];
                member['primary_email'] = email;
                emailString += email + '; ';

                memberObj[member['external_identifier']] = member;

                // get groups ids
                for (let j = 0; j < member['group_contacts'].length; j++) {
                    groupID = member['group_contacts'][j]['group_id'];
                    if (!groupIDs.includes(groupID)) {
                        groupIDs.push(groupID);
                    }
                }
            }

            console.log(groupIDs);

            // get the group names from group_contacts.group_id
            // and assign each member a site and department
            CRM.api4('Group', 'get', {
                select: ["id", "name"],
                where: [["id", "IN", groupIDs], ["name", "LIKE", "%Members"]]
            }).then(function(groups) {
                // loop through members again and assign them site and
                // department based the group_contacts.group_id fields
                console.log(groups);

                let groupObj = {};
                for (let k = 0; k < groups.length; k++) {
                    let group = groups[k];
                    groupObj[group['id']] = group['name'];
                }

                let site = null;
                let department = null;
                for (let i = 0; i < memberData.length; i++) {
                    let member = memberData[i];
                    memberGroups = member['group_contacts'];
                    for (let j = 0; j < memberGroups.length; j++) {
                        let groupID = memberGroups[j]['group_id'];
                        let name = groupObj[groupID];
                        if (name) {
                            name = name.split(' ').slice(0, -1).join(' ');
                        
                            if (sites.includes(name)) {
                                site = name;
                            } else {
                                department = name;
                            }

                            member['site'] = site;
                            member['department'] = department;
                        } else {
                            member['site'] = '';
                            member['department'] = '';
                        }
                    }
                }

                buildTable(memberData, memberObj);
            }, function(failure) {
                // handle failure
            });

            // set the text attribute on email copy btn
            $('#email-list-copy-btn').attr('data-clipboard-text', emailString);

        }, function(failure) {
          // handle failure
          console.log('handling failure');
        });

    })
    
})(jQuery);
