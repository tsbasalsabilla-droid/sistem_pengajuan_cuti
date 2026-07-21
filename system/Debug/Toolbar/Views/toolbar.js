

var ciDebugBar = {
    toolbarContainer: null,
    toolbar: null,
    icon: null,

    init: function () {
        this.toolbarContainer = document.getElementById("toolbarContainer");
        this.toolbar = document.getElementById("debug-bar");
        this.icon = document.getElementById("debug-icon");

        ciDebugBar.createListeners();
        ciDebugBar.setToolbarState();
        ciDebugBar.setToolbarPosition();
        ciDebugBar.setToolbarTheme();
        ciDebugBar.toggleViewsHints();
        ciDebugBar.routerLink();
        ciDebugBar.setHotReloadState();

        document
            .getElementById("debug-bar-link")
            .addEventListener("click", ciDebugBar.toggleToolbar, true);
        document
            .getElementById("debug-icon-link")
            .addEventListener("click", ciDebugBar.toggleToolbar, true);

        historyLoad = this.toolbar.getElementsByClassName("ci-history-load");

        if (historyLoad.length) {
            
            var btn = this.toolbar.querySelector(
                'button[data-time="' + localStorage.getItem("debugbar-time-new") + '"]'
            );
            ciDebugBar.addClass(btn.parentNode.parentNode, "current");


            for (var i = 0; i < historyLoad.length; i++) {
                historyLoad[i].addEventListener(
                    "click",
                    function () {
                        loadDoc(this.getAttribute("data-time"));
                    },
                    true
                );
            }
        }

        
        var tab = ciDebugBar.readCookie("debug-bar-tab");
        if (document.getElementById(tab)) {
            var el = document.getElementById(tab);
            ciDebugBar.switchClass(el, "debug-bar-ndisplay", "debug-bar-dblock");
            ciDebugBar.addClass(el, "active");
            tab = document.querySelector("[data-tab=" + tab + "]");
            if (tab) {
                ciDebugBar.addClass(tab.parentNode, "active");
            }
        }
    },

    createListeners: function () {
        var buttons = [].slice.call(
            this.toolbar.querySelectorAll(".ci-label a")
        );

        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener("click", ciDebugBar.showTab, true);
        }

        
        var links = this.toolbar.querySelectorAll("[data-toggle]");
        for (var i = 0; i < links.length; i++) {
            let toggleData = links[i].getAttribute("data-toggle");
            if (toggleData === "datatable") {

                let datatable = links[i].getAttribute("data-table");
                links[i].addEventListener("click", function() {
                    ciDebugBar.toggleDataTable(datatable)
                }, true);

            } else if (toggleData === "childrows") {

                let child = links[i].getAttribute("data-child");
                links[i].addEventListener("click", function() {
                    ciDebugBar.toggleChildRows(child)
                }, true);

            } else {
                links[i].addEventListener("click", ciDebugBar.toggleRows, true);
            }
        }
    },

    showTab: function () {
        
        var tab = document.getElementById(this.getAttribute("data-tab"));

        
        if (! tab) {
            return;
        }

        
        ciDebugBar.createCookie("debug-bar-tab", "", -1);

        
        var state = tab.classList.contains("debug-bar-dblock");

        
        var tabs = document.querySelectorAll("#debug-bar .tab");

        for (var i = 0; i < tabs.length; i++) {
            ciDebugBar.switchClass(tabs[i], "debug-bar-dblock", "debug-bar-ndisplay");
        }

        
        var labels = document.querySelectorAll("#debug-bar .ci-label");

        for (var i = 0; i < labels.length; i++) {
            ciDebugBar.removeClass(labels[i], "active");
        }

        
        if (! state) {
            ciDebugBar.switchClass(tab, "debug-bar-ndisplay", "debug-bar-dblock");
            ciDebugBar.addClass(this.parentNode, "active");
            
            ciDebugBar.createCookie(
                "debug-bar-tab",
                this.getAttribute("data-tab"),
                365
            );
        }
    },

    addClass: function (el, className) {
        if (el.classList) {
            el.classList.add(className);
        } else {
            el.className += " " + className;
        }
    },

    removeClass: function (el, className) {
        if (el.classList) {
            el.classList.remove(className);
        } else {
            el.className = el.className.replace(
                new RegExp(
                    "(^|\\b)" + className.split(" ").join("|") + "(\\b|$)",
                    "gi"
                ),
                " "
            );
        }
    },

    switchClass  : function(el, classFrom, classTo) {
        ciDebugBar.removeClass(el, classFrom);
        ciDebugBar.addClass(el, classTo);
    },

    
    toggleRows: function (event) {
        if (event.target) {
            let row = event.target.closest("tr");
            let target = document.getElementById(
                row.getAttribute("data-toggle")
            );

            if (target.classList.contains("debug-bar-ndisplay")) {
                ciDebugBar.switchClass(target, "debug-bar-ndisplay", "debug-bar-dtableRow");
            } else {
                ciDebugBar.switchClass(target, "debug-bar-dtableRow", "debug-bar-ndisplay");
            }
        }
    },

    
    toggleDataTable: function (obj) {
        if (typeof obj == "string") {
            obj = document.getElementById(obj + "_table");
        }

        if (obj) {
            if (obj.classList.contains("debug-bar-ndisplay")) {
                ciDebugBar.switchClass(obj, "debug-bar-ndisplay", "debug-bar-dblock");
            } else {
                ciDebugBar.switchClass(obj, "debug-bar-dblock", "debug-bar-ndisplay");
            }
        }
    },

    
    toggleChildRows: function (obj) {
        if (typeof obj == "string") {
            par = document.getElementById(obj + "_parent");
            obj = document.getElementById(obj + "_children");
        }

        if (par && obj) {

            if (obj.classList.contains("debug-bar-ndisplay")) {
                ciDebugBar.removeClass(obj, "debug-bar-ndisplay");
            } else {
                ciDebugBar.addClass(obj, "debug-bar-ndisplay");
            }

            par.classList.toggle("timeline-parent-open");
        }
    },

    

    
    toggleToolbar: function () {
        var open = ! ciDebugBar.toolbar.classList.contains("debug-bar-ndisplay");

        if (open) {
            ciDebugBar.switchClass(ciDebugBar.icon, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
            ciDebugBar.switchClass(ciDebugBar.toolbar, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
        } else {
            ciDebugBar.switchClass(ciDebugBar.icon, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
            ciDebugBar.switchClass(ciDebugBar.toolbar, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
        }

        
        ciDebugBar.createCookie("debug-bar-state", "", -1);
        ciDebugBar.createCookie(
            "debug-bar-state",
            open == true ? "minimized" : "open",
            365
        );
    },

    
    setToolbarState: function () {
        var open = ciDebugBar.readCookie("debug-bar-state");

        if (open != "open") {
            ciDebugBar.switchClass(ciDebugBar.icon, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
            ciDebugBar.switchClass(ciDebugBar.toolbar, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
        } else {
            ciDebugBar.switchClass(ciDebugBar.icon, "debug-bar-dinlineBlock", "debug-bar-ndisplay");
            ciDebugBar.switchClass(ciDebugBar.toolbar, "debug-bar-ndisplay", "debug-bar-dinlineBlock");
        }
    },

    toggleViewsHints: function () {
        
        if (
            localStorage.getItem("debugbar-time") !=
            localStorage.getItem("debugbar-time-new")
        ) {
            var a = document.querySelector('a[data-tab="ci-views"]');
            a.href = "#";
            return;
        }

        var nodeList = []; 
        var sortedComments = [];
        var comments = [];

        var getComments = function () {
            var nodes = [];
            var result = [];
            var xpathResults = document.evaluate(
                "//comment()[starts-with(., ' DEBUG-VIEW')]",
                document,
                null,
                XPathResult.ANY_TYPE,
                null
            );
            var nextNode = xpathResults.iterateNext();
            while (nextNode) {
                nodes.push(nextNode);
                nextNode = xpathResults.iterateNext();
            }

            
            for (var i = 0; i < nodes.length; ++i) {
                
                var path = nodes[i].nodeValue.substring(
                    18,
                    nodes[i].nodeValue.length - 1
                );

                if (nodes[i].nodeValue[12] === "S") {
                    
                    
                    result[path] = [nodes[i], null];
                } else if (result[path]) {
                    
                    result[path][1] = nodes[i];
                }
            }

            return result;
        };

        
        var getParentNode = function (node, targetNode) {
            if (node.parentNode === null) {
                return null;
            }

            if (node.parentNode !== targetNode) {
                return getParentNode(node.parentNode, targetNode);
            }

            return node;
        };

        
        const INVALID_ELEMENTS = ["NOSCRIPT", "SCRIPT", "STYLE"];
        const OUTER_ELEMENTS = ["HTML", "BODY", "HEAD"];

        var getValidElementInner = function (node, reverse) {
            
            if (node === null) {
                return null;
            }

            
            if (OUTER_ELEMENTS.indexOf(node.nodeName) !== -1) {
                for (var i = 0; i < document.body.children.length; ++i) {
                    var index = reverse
                        ? document.body.children.length - (i + 1)
                        : i;
                    var element = document.body.children[index];

                    
                    if (INVALID_ELEMENTS.indexOf(element.nodeName) !== -1) {
                        continue;
                    }

                    return [element, reverse];
                }

                return null;
            }

            
            while (
                node !== null &&
                INVALID_ELEMENTS.indexOf(node.nodeName) !== -1
            ) {
                node = reverse
                    ? node.previousElementSibling
                    : node.nextElementSibling;
            }

            
            if (node === null) {
                return null;
            }

            return [node, reverse];
        };

        
        
        var getValidElement = function (nodeElement) {
            if (nodeElement) {
                if (nodeElement.nextElementSibling !== null) {
                    return (
                        getValidElementInner(
                            nodeElement.nextElementSibling,
                            false
                        ) ||
                        getValidElementInner(
                            nodeElement.previousElementSibling,
                            true
                        )
                    );
                }
                if (nodeElement.previousElementSibling !== null) {
                    return getValidElementInner(
                        nodeElement.previousElementSibling,
                        true
                    );
                }
            }

            
            return null;
        };

        function showHints() {
            
            sortedComments = getComments();

            for (var key in sortedComments) {
                var startElement = getValidElement(sortedComments[key][0]);
                var endElement = getValidElement(sortedComments[key][1]);

                
                if (startElement === null || endElement === null) {
                    continue;
                }

                
                var jointParent = getParentNode(
                    endElement[0],
                    startElement[0].parentNode
                );
                if (jointParent === null) {
                    
                    jointParent = getParentNode(
                        startElement[0],
                        endElement[0].parentNode
                    );
                    if (jointParent === null) {
                        
                        continue;
                    } else {
                        startElement[0] = jointParent;
                    }
                } else {
                    endElement[0] = jointParent;
                }

                var debugDiv = document.createElement("div"); 
                var debugPath = document.createElement("div"); 
                var childArray = startElement[0].parentNode.childNodes; 
                var parent = startElement[0].parentNode;
                let start, end;

                
                debugDiv.classList.add("debug-view");
                debugDiv.classList.add("show-view");
                debugPath.classList.add("debug-view-path");
                debugPath.innerText = key;
                debugDiv.appendChild(debugPath);

                
                
                for (var i = 0; i < childArray.length; ++i) {
                    
                    if (
                        childArray[i] === sortedComments[key][1] ||
                        childArray[i] === sortedComments[key][0] ||
                        childArray[i] === startElement[0]
                    ) {
                        start = i;
                        if (childArray[i] === sortedComments[key][0]) {
                            start++; 
                        }
                        break;
                    }
                }
                
                if (startElement[1]) {
                    start++;
                }

                
                for (var i = start; i < childArray.length; ++i) {
                    if (childArray[i] === endElement[0]) {
                        end = i;
                        
                    } else if (childArray[i] === sortedComments[key][1]) {
                        
                        end = i;
                        break;
                    }
                }

                
                var number = end - start;
                if (endElement[1]) {
                    number++;
                }
                for (var i = 0; i < number; ++i) {
                    if (INVALID_ELEMENTS.indexOf(childArray[start]) !== -1) {
                        
                        start++;
                        continue;
                    }
                    debugDiv.appendChild(childArray[start]);
                }

                
                nodeList.push(parent.insertBefore(debugDiv, childArray[start]));
            }

            ciDebugBar.createCookie("debug-view", "show", 365);
            ciDebugBar.addClass(btn, "active");
        }

        function hideHints() {
            for (var i = 0; i < nodeList.length; ++i) {
                var index;

                
                for (
                    var j = 0;
                    j < nodeList[i].parentNode.childNodes.length;
                    ++j
                ) {
                    if (nodeList[i].parentNode.childNodes[j] === nodeList[i]) {
                        index = j;
                        break;
                    }
                }

                
                while (nodeList[i].childNodes.length !== 1) {
                    nodeList[i].parentNode.insertBefore(
                        nodeList[i].childNodes[1],
                        nodeList[i].parentNode.childNodes[index].nextSibling
                    );
                    index++;
                }

                nodeList[i].parentNode.removeChild(nodeList[i]);
            }
            nodeList.length = 0;

            ciDebugBar.createCookie("debug-view", "", -1);
            ciDebugBar.removeClass(btn, "active");
        }

        var btn = document.querySelector("[data-tab=ci-views]");

        
        if (! btn) {
            return;
        }

        btn.parentNode.onclick = function () {
            if (ciDebugBar.readCookie("debug-view")) {
                hideHints();
            } else {
                showHints();
            }
        };

        
        if (ciDebugBar.readCookie("debug-view")) {
            showHints();
        }
    },

    setToolbarPosition: function () {
        var btnPosition = this.toolbar.querySelector("#toolbar-position");

        if (ciDebugBar.readCookie("debug-bar-position") === "top") {
            ciDebugBar.addClass(ciDebugBar.icon, "fixed-top");
            ciDebugBar.addClass(ciDebugBar.toolbar, "fixed-top");
        }

        btnPosition.addEventListener(
            "click",
            function () {
                var position = ciDebugBar.readCookie("debug-bar-position");

                ciDebugBar.createCookie("debug-bar-position", "", -1);

                if (! position || position === "bottom") {
                    ciDebugBar.createCookie("debug-bar-position", "top", 365);
                    ciDebugBar.addClass(ciDebugBar.icon, "fixed-top");
                    ciDebugBar.addClass(ciDebugBar.toolbar, "fixed-top");
                } else {
                    ciDebugBar.createCookie(
                        "debug-bar-position",
                        "bottom",
                        365
                    );
                    ciDebugBar.removeClass(ciDebugBar.icon, "fixed-top");
                    ciDebugBar.removeClass(ciDebugBar.toolbar, "fixed-top");
                }
            },
            true
        );
    },

    setToolbarTheme: function () {
        var btnTheme = this.toolbar.querySelector("#toolbar-theme");
        var isDarkMode = window.matchMedia(
            "(prefers-color-scheme: dark)"
        ).matches;
        var isLightMode = window.matchMedia(
            "(prefers-color-scheme: light)"
        ).matches;

        
        if (ciDebugBar.readCookie("debug-bar-theme") === "dark") {
            ciDebugBar.removeClass(ciDebugBar.toolbarContainer, "light");
            ciDebugBar.addClass(ciDebugBar.toolbarContainer, "dark");
        } else if (ciDebugBar.readCookie("debug-bar-theme") === "light") {
            ciDebugBar.removeClass(ciDebugBar.toolbarContainer, "dark");
            ciDebugBar.addClass(ciDebugBar.toolbarContainer, "light");
        }

        btnTheme.addEventListener(
            "click",
            function () {
                var theme = ciDebugBar.readCookie("debug-bar-theme");

                if (
                    ! theme &&
                    window.matchMedia("(prefers-color-scheme: dark)").matches
                ) {
                    
                    
                    ciDebugBar.createCookie("debug-bar-theme", "light", 365);
                    ciDebugBar.removeClass(ciDebugBar.toolbarContainer, "dark");
                    ciDebugBar.addClass(ciDebugBar.toolbarContainer, "light");
                } else {
                    if (theme === "dark") {
                        ciDebugBar.createCookie(
                            "debug-bar-theme",
                            "light",
                            365
                        );
                        ciDebugBar.removeClass(
                            ciDebugBar.toolbarContainer,
                            "dark"
                        );
                        ciDebugBar.addClass(
                            ciDebugBar.toolbarContainer,
                            "light"
                        );
                    } else {
                        
                        
                        ciDebugBar.createCookie("debug-bar-theme", "dark", 365);
                        ciDebugBar.removeClass(
                            ciDebugBar.toolbarContainer,
                            "light"
                        );
                        ciDebugBar.addClass(
                            ciDebugBar.toolbarContainer,
                            "dark"
                        );
                    }
                }
            },
            true
        );
    },

    setHotReloadState: function () {
        var btn = document.getElementById("debug-hot-reload").parentNode;
        var btnImg = btn.getElementsByTagName("img")[0];
        var eventSource;

        
        if (! btn) {
            return;
        }

        btn.onclick = function () {
            if (ciDebugBar.readCookie("debug-hot-reload")) {
                ciDebugBar.createCookie("debug-hot-reload", "", -1);
                ciDebugBar.removeClass(btn, "active");
                ciDebugBar.removeClass(btnImg, "rotate");

                
                if (typeof eventSource !== "undefined") {
                    eventSource.close();
                    eventSource = void 0; 
                }
            } else {
                ciDebugBar.createCookie("debug-hot-reload", "show", 365);
                ciDebugBar.addClass(btn, "active");
                ciDebugBar.addClass(btnImg, "rotate");

                eventSource = ciDebugBar.hotReloadConnect();
            }
        };

        
        if (ciDebugBar.readCookie("debug-hot-reload")) {
            ciDebugBar.addClass(btn, "active");
            ciDebugBar.addClass(btnImg, "rotate");
            eventSource = ciDebugBar.hotReloadConnect();
        }
    },

    hotReloadConnect: function () {
        const eventSource = new EventSource(ciSiteURL + "/__hot-reload");

        eventSource.addEventListener("reload", function (e) {
            console.log("reload", e);
            window.location.reload();
        });

        eventSource.onerror = (err) => {
            console.error("EventSource failed:", err);
        };

        return eventSource;
    },

    
    createCookie: function (name, value, days) {
        if (days) {
            var date = new Date();

            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);

            var expires = "; expires=" + date.toGMTString();
        } else {
            var expires = "";
        }

        document.cookie =
            name + "=" + value + expires + "; path=/; samesite=Lax";
    },

    readCookie: function (name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(";");

        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == " ") {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) == 0) {
                return c.substring(nameEQ.length, c.length);
            }
        }
        return null;
    },

    trimSlash: function (text) {
        return text.replace(/^\/|\/$/g, "");
    },

    routerLink: function () {
        var row, _location;
        var rowGet = this.toolbar.querySelectorAll(
            'td[data-debugbar-route="GET"]'
        );
        var patt = /\(.+?\)/g;

        for (var i = 0; i < rowGet.length; i++) {
            row = rowGet[i];
            if (!/\/\(.+?\)/.test(rowGet[i].innerText)) {
                ciDebugBar.addClass(row, "debug-bar-pointer");
                row.setAttribute(
                    "title",
                    location.origin + "/" + ciDebugBar.trimSlash(row.innerText)
                );
                row.addEventListener("click", function (ev) {
                    _location =
                        location.origin +
                        "/" +
                        ciDebugBar.trimSlash(ev.target.innerText);
                    var redirectWindow = window.open(_location, "_blank");
                    redirectWindow.location;
                });
            } else {
                row.innerHTML =
                    "<div>" +
                    row.innerText +
                    "</div>" +
                    '<form data-debugbar-route-tpl="' +
                    ciDebugBar.trimSlash(row.innerText.replace(patt, "?")) +
                    '">' +
                    row.innerText.replace(patt, function (match) {
                        return '<input type="text" placeholder="' + match + '">';
                    }) +
                    '<input type="submit" value="Go" class="debug-bar-mleft4">' +
                    "</form>";
            }
        }

        rowGet = this.toolbar.querySelectorAll(
            'td[data-debugbar-route="GET"] form'
        );
        for (var i = 0; i < rowGet.length; i++) {
            row = rowGet[i];

            row.addEventListener("submit", function (event) {
                event.preventDefault();
                var inputArray = [],
                    t = 0;
                var input = event.target.querySelectorAll("input[type=text]");
                var tpl = event.target.getAttribute("data-debugbar-route-tpl");

                for (var n = 0; n < input.length; n++) {
                    if (input[n].value.length > 0) {
                        inputArray.push(input[n].value);
                    }
                }

                if (inputArray.length > 0) {
                    _location =
                        location.origin +
                        "/" +
                        tpl.replace(/\?/g, function () {
                            return inputArray[t++];
                        });

                    var redirectWindow = window.open(_location, "_blank");
                    redirectWindow.location;
                }
            });
        }
    },
};
