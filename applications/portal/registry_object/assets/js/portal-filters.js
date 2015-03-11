angular.module('portal-filters', [])
	.filter('filter_name', function(){
		return function(text) {
			switch(text) {
				case 'q': return 'All' ;break;
				case 'cq': return 'Advanced Query' ;break;
				case 'title': return 'Title' ;break;
				case 'identifier': return 'Identifier' ;break;
				case 'related_people': return 'Related People' ;break;
				case 'related_organisation': return 'Related Organisations' ;break;
				case 'description': return 'Description' ;break;
				case 'subject': return 'Subjects' ;break;
				case 'access_rights': return 'Access'; break;
				case 'group': return 'Data Provider'; break;
				case 'license_class': return 'Licence'; break;
				case 'type': return 'Type'; break;
				case 'subject_vocab_uri': return 'Subject Vocabulary URI'; break;
				case 'anzsrc-for': return 'Subjects ANZSRC-FOR'; break;
				case 'anzsrc-seo': return 'Subjects ANZSRC-SEO'; break;
				case 'year_from': return 'Time Period (from)'; break;
				case 'year_to': return 'Time Period (to)'; break;
				case 'funding_scheme': return 'Funding Scheme'; break;
				case 'funders': return 'Funders'; break;
				case 'administering_institution': return 'Administering Institution'; break;
				case 'activity_status': return 'Status'; break;
				case 'related_party_one_id': return 'Related Researcher'; break;
				case 'scot': return 'Schools of Online Thesaurus'; break;
				case 'pont': return 'Powerhouse Museum Object Name Thesaurus'; break;
				case 'psychit': return 'Thesaurus of psychological index terms'; break;
				case 'anzsrc': return 'ANZSRC'; break;
				case 'apt': return 'Australian Pictorial Thesaurus'; break;
				case 'gcmd': return 'GCMD Keywords'; break;
				case 'lcsh': return 'LCSH'; break;
				case 'keywords': return 'Keywords'; break;
				case 'refine': return 'Refine'; break;
				default: return text;
			}
		}
	})
	.filter('filter_value', function($sce){
		return function(text) {
			if (angular.isArray(text)) {
				var html = '<ul>';
				angular.forEach(text, function(content) {
					html+='<li>'+content+'</li>';
				});
				html+='</ul>';
				return $sce.trustAsHtml(html);
			} else {
				return $sce.trustAsHtml(text);
			}
		}
	})
	.filter('toTitleCase', function($log){
		return function(str){
			return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});	
		}
	})
	.filter('getLabelFor', function($log){
		return function(value, filter) {
			var ret = '';
			angular.forEach(filter, function(f){
				if(f.value==value) {
					ret = f.label;
				}
			});
			return ret;
		}
	})
	.filter('truncate', function () {
		return function (text, length, end) {
			if(text){
				if (isNaN(length))
					length = 10;
				if (end === undefined)
					end = "...";
				if (text.length <= length || text.length - end.length <= length) {
					return text;
				}
				else {
					return String(text).substring(0, length-end.length) + end;
				}
			}
		};
	})
	.filter('text', ['$sce', function($sce){
		return function(text){
			var tmp = document.createElement("DIV");
			tmp.innerHTML = text;
			return tmp.textContent || tmp.innerText || "";
			// var decoded = $('<div/>').html(text).text();
			// return decoded;
		}
	}])
	.filter('trustAsHtml', ['$sce', function($sce){
		return function(text){
			var decoded = $('<div/>').html(text).text();
			return $sce.trustAsHtml(decoded);
		}
	}])
	.filter("timeago", function () {
	    //time: the time
	    //local: compared to what time? default: now
	    //raw: wheter you want in a format of "5 minutes ago", or "5 minutes"
	    return function (time, local, raw) {
	        if (!time) return "never";
	 
	        if (!local) {
	            (local = Date.now())
	        }

	 
	        if (angular.isDate(time)) {
	            time = time.getTime();
	        } else if (typeof time === "string") {
	        	var s = time;
				var bits = s.split(/\D/);
				var date = new Date(bits[0], --bits[1], bits[2], bits[3], bits[4]);
				time = date.getTime();
	        }

	     
	        if (angular.isDate(local)) {
	            local = local.getTime();
	        }else if (typeof local === "string") {
	            local = new Date(local).getTime();
	        }

	        // console.log(local, time);
	 
	        if (typeof time !== 'number' || typeof local !== 'number' || isNaN(time) || isNaN(local)) {
	            return;
	        }
	 
	        var
	            offset = Math.abs((local - time) / 1000),
	            span = [],
	            MINUTE = 60,
	            HOUR = 3600,
	            DAY = 86400,
	            WEEK = 604800,
	            MONTH = 2629744,
	            YEAR = 31556926,
	            DECADE = 315569260;
	        
	 
	        if (offset <= MINUTE)              span = [ '', raw ? 'now' : parseInt(offset) + ' seconds' ];
	        else if (offset < (MINUTE * 60))   span = [ Math.round(Math.abs(offset / MINUTE)), 'min' ];
	        else if (offset < (HOUR * 24))     span = [ Math.round(Math.abs(offset / HOUR)), 'hr' ];
	        else if (offset < (DAY * 7))       span = [ Math.round(Math.abs(offset / DAY)), 'day' ];
	        else if (offset < (WEEK * 52))     span = [ Math.round(Math.abs(offset / WEEK)), 'week' ];
	        else if (offset < (YEAR * 10))     span = [ Math.round(Math.abs(offset / YEAR)), 'year' ];
	        else if (offset < (DECADE * 100))  span = [ Math.round(Math.abs(offset / DECADE)), 'decade' ];
	        else if (isNaN(offset))			   span = [''];
	        else                               span = [ '', 'a long time' ];
	 
	        span[1] += (span[0] === 0 || span[0] > 1) ? 's' : '';
	        span = span.join(' ');
	 
	        if (raw === true) {
	            return span;
	        }
	        return (time <= local && !isNaN(time)) ? span + ' ago' : 'in ' + span;
	    }
	})
	.filter('orderObjectBy', function() {
	  return function(items, field, reverse) {
	    var filtered = [];
	    angular.forEach(items, function(item) {
	      filtered.push(item);
	    });
	    filtered.sort(function (a, b) {
	      return (a[field] > b[field] ? 1 : -1);
	    });
	    if(reverse) filtered.reverse();
	    return filtered;
	  };
});

;