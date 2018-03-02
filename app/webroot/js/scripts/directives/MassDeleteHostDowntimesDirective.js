angular.module('openITCOCKPIT').directive('massDeleteHostDowntimes', function($http, $filter, $timeout){
    return {
        restrict: 'E',
        replace: true,
        templateUrl: '/angular/mass_delete_host_downtimes.html',

        controller: function($scope){

            $scope.includeServices = true;
            $scope.objects = {};
            $scope.percentage = 0;
            $scope.isDeleting = false;

            $scope.myDeleteUrl = $scope.deleteUrl;

            var callbackName = false;

            $scope.setObjectsForMassHostDowntimeDelete = function(objects){
                $scope.objects = objects;
            };

            $scope.setCallbackForMassHostDowntimeDelete = function(_callback){
                callbackName = _callback;
            };

            $scope.delete = function(){
                $scope.isDeleting = true;
                var count = Object.keys($scope.objects).length;
                var i = 0;

                for(var id in $scope.objects){
                    var data = {
                        includeServices: $scope.includeServices,
                        type: 'host'
                    };
                    $http.post($scope.myDeleteUrl + id + ".json", data).then(
                        function(result){
                            i++;
                            $scope.percentage = Math.round(i / count * 100);

                            if(i === count){
                                $scope.isDeleting = false;
                                $scope.percentage = 0;

                                $('#angularMassDeleteHostDowntimes').modal('hide');

                                //Call callback function if given
                                if(callbackName){
                                    $scope[callbackName]();
                                }else{
                                    $scope.load();
                                }
                            }
                        });
                }
            };

        },

        link: function($scope, element, attr){
            $scope.confirmHostDowntimeDelete = function(objects){

                if(attr.hasOwnProperty('deleteUrl')){
                    $scope.myDeleteUrl = attr.deleteUrl;
                }

                if(attr.hasOwnProperty('callback')){
                    $scope.setCallbackForMassHostDowntimeDelete(attr.callback);
                }

                $scope.setObjectsForMassHostDowntimeDelete(objects);
                $('#angularMassDeleteHostDowntimes').modal('show');
            };
        }
    };
});