// data.action - "login" "signup" "add" "edit" "delete" "logout" "event" "share"

/** Calender
 *  Represent a Calender
 *
 *  Properties:
 *      .date  == a Date object
 *      .month == a Month object
 *      .weeks == 5 weeks with 7 days in each week
 *      .curevent == current selected event
 *      .events == events in current month
 *      .userinfo == login user information
 *              .status == true if login
 *              .uid    == user id
 *              .token  == CSRF token
 *      .tag == work study tag
 *              .study  == true if need to display
 *              .work   == true if need to display
 *      .holiday == holiday info
 *                    
 *  Functions:
 *      .login(result)  set login user information
 *      .logout()    reset login user information
 *      .fillGrid()  generate html element for #calendar-grid
 *      .update()    update .weeks
 *      .nextMonth() set .month to next month
 *      .prevMonth() set .month to prev month
 *      .setDate(date)   set .month to specify date
 *      .displayEvent(event)  display selected event
 *      .hideEvent(event)    hide selected event
 */
function Calendar(date){
    this.month = new Month(date.getFullYear(), date.getMonth());
    this.weeks = [];
    this.events = [];
    this.curevent = null;
    this.userinfo = {"status":false};
    this.tag = {};
    this.tag.normal=true;
    this.tag.study=true;
    this.tag.work=true;
    this.holidays={};
    for(let i=0;i<5;i++){
        this.weeks.push(new Array(7));
    }
    const that = this;
    //https://www.calendarindex.com/   free holiday API
    $.get("https://www.calendarindex.com/api/v1/holidays?country=US&year=2018&state=MO&api_key=a836954d4436308dfaa1cd79db0da5efbecd1ce1",function(data){
        let firstday = that.weeks[0][0].date;
        let lastday = that.weeks[4][6].date;
        let oneday = 24*60*60*1000; // one day's time
        lastday.setDate(lastday.getDate() + 1);
        that.holidays = data.response.holidays;
        let holidays = that.holidays;
        for(let v in holidays){
            let day = new Date(holidays[v].date.split('-').join(' '));
            if(day < firstday || day >= lastday){
                continue;
            }
            let holidayatt = "overflow-control rounded mb-1 mx-1 px-2 justify-content-center text-white click-clear blueviolet";
            let d = document.createElement("div");
            $(d).attr("class", holidayatt);
            $(d).attr("id", "holiday");
            $(d).text(decodeEntities(holidays[v].name));
            let cur = new Date(holidays[v].start.split('T')[0].split('-').join(' '));
            let end = new Date(holidays[v].end.split('T')[0].split('-').join(' '));
            while(cur < end){
                let diff = Math.floor((cur - firstday)/oneday);
                let i = Math.floor(diff/7);
                let j = diff%7;
                let daynode = $("#calendar-grid").children()[i];
                daynode = $(daynode).children()[j];
                daynode.append(d);
                cur.setDate(cur.getDate()+1);
            }
        }
    });
    
    
    this.logout = function(){
        this.events = [];
        this.curevent = null;
        this.userinfo = {"status":false};
        document.cookie = `uid=;token=;`;
        this.fillGrid();
        this.hideEvent();
        $("#to-add").hide();
        $(".click-clear").click(function(){return false;});
        $("#login-nav").show();
        $("#logout-nav").hide();
        
    };
    
    this.login = function(result){
        this.userinfo.status = result.status;
        this.userinfo.uid = result.uid;
        this.userinfo.token = result.token;
        document.cookie = `uid=${result.uid}`;
        document.cookie = `token=${result.token}`;
        if (this.userinfo.status) {
            $("#to-add").show();
            $("#login-nav").hide();
            $("#logout-nav").show();
        }
    };
    
    this.fillGrid = function(){
        const weekatt = "d-flex flex-110";
        let grid = $("#calendar-grid");
        this.update();
        $(grid).html("");
        $(".click-clear").click(function(){return false;});
        for(let i=0; i<5; i++){
            let week = document.createElement("div");
            $(week).attr("class", weekatt);
            for(let j=0; j<7;j++){
                let daynode = this.weeks[i][j].getNode();
                $(daynode).attr("id", `date-${i}-${j}`);
                $(week).append(daynode);
                if(this.userinfo.status){
                    let datestr = dateFormat(this.weeks[i][j].date);
                    (function(date, i, j){
                        $(document).on("click",`#date-${i}-${j}`,function(e){
                            if(e.target == this || e.target.id == "day-number" || e.target.id == "holiday"){
                                $("#add-event-modal").modal();
                                $("#event-date").attr("value", date);
                            }
                        });
                    })(datestr, i, j);
                }
            }
            $(grid).append(week);
        }

        
        let firstday = this.weeks[0][0].date;
        let lastday = this.weeks[4][6].date;
        lastday.setDate(lastday.getDate() + 1);
        let oneday = 24*60*60*1000; // one day's time
        
        
        let holidays = this.holidays;
        for(let v in holidays){
            let day = new Date(holidays[v].date.split('-').join(' '));
            if(day < firstday || day >= lastday){
                continue;
            }
            let holidayatt = "overflow-control rounded mb-1 mx-1 px-2 justify-content-center text-white click-clear blueviolet";
            let d = document.createElement("div");
            $(d).attr("class", holidayatt);
            $(d).attr("id", "holiday");
            $(d).text(decodeEntities(holidays[v].name));
            let cur = new Date(holidays[v].start.split('T')[0].split('-').join(' '));
            let end = new Date(holidays[v].end.split('T')[0].split('-').join(' '));
            while(cur < end){
                let diff = Math.floor((cur - firstday)/oneday);
                let i = Math.floor(diff/7);
                let j = diff%7;
                let daynode = $(grid).children()[i];
                daynode = $(daynode).children()[j];
                daynode.append(d);
                cur.setDate(cur.getDate()+1);
            }
        }

        
        if(this.userinfo.status){
            let data = {};
            this.events =[];
            data.action = "event";
            data.firstday = dateFormat(this.weeks[0][0].date);
            data.lastday = dateFormat(this.weeks[4][6].date);
            data.token = this.userinfo.token;
            const that = this;
            $.post(server, data, function(data,status){
                if(status=='success'){
                    if(data.status){
                        for(let v in data.events){
                            event = data.events[v];
                            that.addEvent(new Event(event.eventid, event.title, event.date, event.time, event.tag));
                        }
                        for(let v in that.events){
                            if(!that.tag[that.events[v].tag]){
                                continue;
                            }
                            let eventatt = "overflow-control rounded mb-1 mx-1 px-2 justify-content-center text-white click-clear ";
                            const tagcolor = {
                                "normal":"bg-info",
                                "work":"bg-success",
                                "study":"bg-warning"
                            };
                            eventatt = eventatt + tagcolor[that.events[v].tag];
                            let event = document.createElement("div");
                            $(event).attr("class", eventatt);
                            $(event).attr("id",`event-id-${that.events[v].eventid}`);
                            $(event).text(that.events[v].title);
                            let diff = Math.floor((new Date(that.events[v].date) - firstday)/oneday);
                            let i = Math.floor(diff/7);
                            let j = diff%7;
                            let daynode = $(grid).children()[i];
                            daynode = $(daynode).children()[j];
                            daynode.append(event);
                            
                            (function(v ,curevent, id){
                                $(document).on("click",`#event-id-${id}`,function(){
                                    that.curevent = curevent;
                                    that.displayEvent();
                                });
                            })(v, that.events[v], that.events[v].eventid);
                        }
                    } 
                }else{
                    alert("load events failed");
                }
            });
        }
   

        $("#text-month").text(this.month.getDateObject(1).toLocaleString("en-US",{month:"long", year:"numeric"}));
    };
    
    this.setDate = function(date){
        this.month = new Month(date.getFullYear(), date.getMonth());
    };
    
    this.update = function(){
        let rawweeks = this.month.getWeeks();
        this.weeks = rawweeks;
        for(let i in rawweeks){
            let days = rawweeks[i].getDates(); 
            for(let j in days){
                this.weeks[i][j] = new DateGrid(days[j]);
            }
        }
    };
    
    this.nextMonth = function(){
        this.month = this.month.nextMonth();
        this.events = [];
        this.fillGrid();
    };
    
    this.prevMonth = function(){
        this.month = this.month.prevMonth();
        this.events = [];
        this.fillGrid();
    };
    
    this.displayEvent = function(){
        let grid = $("#display-event");
        $(grid).html("");
        $(grid).append("<br/>");
        let textnode = document.createElement("h4");
        $(textnode).text(decodeEntities(this.curevent.title));
        $(grid).append(textnode);
        $(grid).append("<br/>");
        $(grid).append(`<p><i class="far fa-calendar"></i>&nbsp${dateFormat(this.curevent.date)}</p>`);
        $(grid).append(`<p><i class="far fa-clock"></i>&nbsp${timeFormat(this.curevent.date)}</p>`);
        $(grid).show();
        $("#to-edit").show();
        $("#to-delete").show();
        $("#share-event").show();
    };
    
    this.hideEvent = function(){
        $("#to-edit").hide();
        $("#to-delete").hide();
        $("#display-event").hide();
        $("#share-event").hide();
    };
    
    this.addEvent = function(event){
        this.events.push(event);
        
    };
}

/* DateGrid
 * Represent a grid for one day with date and events information
 *
 * Properties:
 *      .date == date in this grid
 *      .events == events in this grid
 *
 * Functions:
 *      .addEvent(event)  add new event to event array
 *      .getNode()    return Node for this grid
 */
function DateGrid(date){
    this.date = date;
    this.events = [];
    
    this.addEvent = function(event){
        this.events.push(event);
    };
    
    this.getNode = function(){
        // not exploit jQuery, try rewrite
        const gridatt = "text-nowrap d-flex flex-column border flex-110 auto-overflow click-clear";
        const dateatt = "m-1";
        let grid = document.createElement("div");
        $(grid).attr("class", gridatt);
        let day = document.createElement("div");
        $(day).text(this.date.getDate());
        $(day).attr("class", dateatt);
        $(day).attr("id", "day-number");
        $(grid).append(day);
        return grid;
    };
}

// TODO: database design, unite datetime format
function Event(eventid, title, date, time, tag){
    this.eventid = eventid;
    this.title = title;
    date = date.replace('-',' ');
    this.date = new Date(date+" "+time);
    this.tag = tag;
}

// Escaping for Non-HTML Output
// https://stackoverflow.com/a/1395954
function decodeEntities(encodedString) {
    var textArea = document.createElement('textarea');
    textArea.innerHTML = encodedString;
    return textArea.value;
}

// to yyyy-mm-dd
function dateFormat(date){
    let year = date.getFullYear().toString();
    let month = (date.getMonth()+1).toString();
    let day = date.getDate().toString();
    if(month.length< 2){
        month = "0"+month;
    }
    if(day.length < 2){
        day = "0"+ day;
    }
    return year+"-"+month+"-"+day;
}
// to hh:mm
function timeFormat(date){
    let hour = date.getHours().toString();
    let min = date.getMinutes().toString();
    hour = hour.length < 2 ? "0"+hour : hour;
    min = min.length <2 ? "0"+min : min;
    return hour+":"+min;
}

function getcookie(cname) {
    cookie = document.cookie;
    //https://stackoverflow.com/a/25490531
    re = new RegExp(`(?:^|;)\\s*${cname}\\s*=\\s*([^;]+)`);
    match = re.exec(cookie);
    if (match!=null && match[1].trim().length > 0){
        return match[1];
    }
    else{
        return null;
    }
}

const regex={
    // username validation a, a_b, a.b
    "username": new RegExp(/^[a-zA-Z0-9]+([._]?[a-zA-Z0-9]+)*$/),
    // password validation, at least one uppercase, one lower case, one number and one special character with length at least 6
    "pwd": new RegExp(/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[#$^+=!*_@%&]).{6,}$/),
    // date regexp, yyyy-mm-dd
    "date": new RegExp(/^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$/),
};
// Main
let curdate = new Date();
let calendar = new Calendar(curdate);
const server = "calendar.php";



$(document).ready(function(){
    let uinfo={};
    uinfo.uid = getcookie('uid');
    uinfo.token = getcookie('token');
    if (uinfo.uid!=null && uinfo.token!=null) {
        uinfo.status = true;
        calendar.login(uinfo);
    }
    calendar.fillGrid();
    
    $("#prev-month").click(function(){
       calendar.prevMonth();
       calendar.hideEvent();
    });
    $("#next-month").click(function(){
       calendar.nextMonth();
       calendar.hideEvent();
    });
    $("#to-add").click(function(){
        $("#add-event-modal").modal();
        $("#event-date").attr("value", dateFormat(new Date()));
        $("#event-time").attr("value", timeFormat(new Date()));
    });
    $("#to-edit").click(function(){
        $("#edit-event-modal").modal();
        $("#edit-event-title").attr("value", decodeEntities(calendar.curevent.title));
        $("#edit-event-date").attr("value", dateFormat(calendar.curevent.date));
        $("#edit-event-time").attr("value", timeFormat(calendar.curevent.date));
    });
    $("#login-btn").click(function(){
        do{
            let data = {};
            let result = {};
            data.action = "login";
            data.username = $("#login-user").val();
            data.password = $("#login-pwd").val();
            if (!regex.username.test(data.username) || !regex.pwd.test(data.password)) {
                $("#login-helper").show();
                break;
            }
            $.post(server, data, function(data, status){
                if (status == 'success') {
                    result = data;
                    if(!result.status){
                        $("#login-helper").show();
                    }else{
                        calendar.login(result);
                        calendar.fillGrid();
                        $("#login-modal").modal("hide");
                        $("#login-pwd").val('');
                        $(".helper").hide();
                    }
                }else{
                    alert("failed");
                }
            });
        }while(0);
    });
    $("#signup-btn").click(function(){
        do{
            let data = {};
            data.action = "signup";
            data.username = $("#signup-user").val();
            data.password1 = $("#signup-pwd1").val();
            data.password2 = $("#signup-pwd2").val();
            if (!regex.username.test(data.username)) {
                $("#name-helper").show();
                break;
            }
            if (!regex.pwd.test(data.password1)) {
                $("#pwd1-helper").show();
                break;
            }
            if (data.password1 != data.password2) {
                $("#pwd2-helper").show();
                break;
            }
            $.post(server, data, function(data, status){
               if (status == 'success') {
                   result = data;
                   if(!result.status){  
                   }else{
                        $("#signup-modal").modal("hide");
                        $("#signup-pwd1").val('');
                        $("#signup-pwd2").val('');
                        $(".helper").hide();
                   }
               }else{
                   alert("failed");
               }
           });
        }while(0);
     });
    
    $("#addevent-btn").click(function(){
        let data = {};
        data.action = "add";
        data.title = $("#event-title").val();
        data.date = $("#event-date").val();
        data.time = $("#event-time").val();
        data.tag = $("input[name='add-event-tag']:checked").val();
        data.token = calendar.userinfo.token;
        $.post(server, data, function(data,status){
            if (status == 'success') {
                result = data;
                if(!result.status){
                    alert("Add event failed.");
                }else{
                    alert("Add event success.");
                    calendar.fillGrid();
                    $("#add-event-modal").modal("hide");
                }
            }else{
                alert("failed");
            }
        });
    
    });
    
    $("#editevent-btn").click(function(){
        let data={};
        data.action = "edit";
        $("#edit-event-title").attr("value", decodeEntities(calendar.curevent.title));
        $("#edit-event-date").attr("value", dateFormat(calendar.curevent.date));
        $("#edit-event-time").attr("value", timeFormat(calendar.curevent.date));
        data.title = $("#edit-event-title").val();
        data.date = $("#edit-event-date").val();
        data.time = $("#edit-event-time").val();
        data.tag = $("input[name='edit-event-tag']:checked").val();
        data.token = calendar.userinfo.token;
        data.eventid = calendar.curevent.eventid;
        $.post(server, data, function(data,status){
            if (status == 'success') {
                result = data;
                if(!result.status){
                    alert("Edit event failed.");
                }else{
                    alert("Edit event success.");
                    calendar.hideEvent();
                    calendar.fillGrid();
                    $("#edit-event-modal").modal("hide");
                }
            }else{
                alert("failed");
            }
        });
    });
    
    $("#logout-btn").click(function(){
        calendar.logout();
        $.post(server, {"action":"logout"}, function(data,status){
            console.log(status);
            $(".click-clear").click(function(){return false;});
        });
        
    });
    $("#to-delete").click(function(){
        let data={};
        data.token = calendar.userinfo.token;
        data.action = "delete";
        data.eventid = calendar.curevent.eventid;
        $.post(server, data, function(data,status){
            console.log(status);
            calendar.curevent = null;
            calendar.hideEvent();
            calendar.fillGrid();
        });
    });
    
    $("#tag-study").change(function(){
        if ($("#tag-study").is(":checked")) {
            calendar.tag.study=true;
        }else{
            calendar.tag.study=false;
        }
        calendar.fillGrid();
    });
    
    $("#tag-work").change(function(){
        if ($("#tag-work").is(":checked")) {
            calendar.tag.work=true;
        }else{
            calendar.tag.work=false;
        }
        calendar.fillGrid();
    });
    
    $("#share-event").click(function(){
        $("#share-event-modal").modal();
    });
    
    $("#share-event-btn").click(function(){
        do{
            let data={};
            data.action="share";
            let taruser = $("#target-user").val();
            if(!regex.username.test(taruser)){
                $("#share-helper").show();
                break;
            }
            data.taruser = taruser;
            data.eventid = calendar.curevent.eventid;
            data.token = calendar.userinfo.token;
            $.post(server,data,function(data,status){
                if (status == 'success') {
                    result = data;
                    if(!result.status){
                        $("#share-helper").show();
                    }else{
                        alert("Share event success.");
                        $("#share-helper").hide();
                        $("#share-event-modal").modal("hide");
                    }
                }else{
                    alert("failed");
                }
            });
            
        }while(0);
    });
});